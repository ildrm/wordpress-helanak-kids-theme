/* global jQuery, inlineEditPost, helanakQuickPrices, ajaxurl */
(function ($) {
	'use strict';
	$(function () {
		var request = null;
		var sequence = 0;
		var nativeSave = inlineEditPost.save;

		function openPrices(id) {
			sequence += 1;
			var current = sequence;
			if (request) { request.abort(); }
			var $row = $('#edit-' + id);
			var $panel = $row.find('.helanak-quick-prices');
			if (!$row.length || $('#woocommerce_inline_' + id + ' .product_type').text() !== 'variable') { return; }
			$panel.insertBefore($row.find('.inline-edit-save')).prop('hidden', false);
			$panel.find('tbody').empty();
			$panel.find('[name="helanak_variation_prices"]').val('{}');
			$panel.attr('aria-busy', 'true');
			$panel.find('.helanak-quick-prices-status').text(helanakQuickPrices.loading);
			$panel.find('.helanak-quick-prices-retry').prop('hidden', true);
			request = $.post(ajaxurl, { action: 'helanak_quick_edit_prices', product_id: id, nonce: helanakQuickPrices.nonce });
			request.done(function (response) {
				if (current !== sequence || !$.contains(document, $row[0])) { return; }
				if (!response.success) {
					$panel.find('.helanak-quick-prices-status').text(response.data && response.data.limit ? helanakQuickPrices.limit : helanakQuickPrices.failed);
					$panel.find('.helanak-quick-prices-retry').prop('hidden', false);
					return;
				}
				var rows = response.data.rows;
				$panel.find('.helanak-quick-prices-status').text(rows.length ? '' : helanakQuickPrices.empty);
				$.each(rows, function (index, item) {
					var $tr = $('<tr>').attr('data-helanak-variation', item.id).data('original', item.original);
					$('<th scope="row">').attr('id', 'helanak-variation-label-' + item.id).text(item.label + ' (#' + item.id + ')').appendTo($tr);
					$.each(['regular', 'sale'], function (position, field) {
						var $cell = $('<td>').attr('data-label', $panel.find('thead th').eq(position + 1).text()).appendTo($tr);
						$('<input type="text" inputmode="decimal" autocomplete="off" dir="ltr">')
							.attr('data-helanak-price', field)
							.attr('aria-labelledby', 'helanak-' + field + '-heading-' + id + ' helanak-variation-label-' + item.id)
							.attr('maxlength', '32').val(item[field]).data('initial', item[field]).appendTo($cell);
					});
					$tr.appendTo($panel.find('tbody'));
				});
				$panel.find('thead th').eq(1).attr('id', 'helanak-regular-heading-' + id);
				$panel.find('thead th').eq(2).attr('id', 'helanak-sale-heading-' + id);
			}).fail(function (xhr, status) {
				if (status === 'abort' || current !== sequence || !$.contains(document, $row[0])) { return; }
				$panel.find('.helanak-quick-prices-status').text(xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.limit ? helanakQuickPrices.limit : helanakQuickPrices.failed);
				$panel.find('.helanak-quick-prices-retry').prop('hidden', false);
			}).always(function () {
				if (current === sequence && $.contains(document, $row[0])) { $panel.attr('aria-busy', 'false'); }
			});
		}

		$('#the-list').on('click.helanakQuickPrices', '.editinline', function () {
			var id = inlineEditPost.getId(this);
			window.setTimeout(function () { openPrices(id); }, 0);
		}).on('click.helanakQuickPrices', '.helanak-quick-prices-retry', function () {
			openPrices($(this).closest('.inline-edit-row').attr('id').replace('edit-', ''));
		});

		inlineEditPost.save = function (object) {
			var id = typeof object === 'object' ? this.getId(object) : object;
			var $panel = $('#edit-' + id + ' .helanak-quick-prices');
			var changes = {};
			$panel.find('tbody tr').each(function () {
				var $tr = $(this);
				var $regular = $tr.find('[data-helanak-price="regular"]');
				var $sale = $tr.find('[data-helanak-price="sale"]');
				if ($regular.val() !== $regular.data('initial') || $sale.val() !== $sale.data('initial')) {
					changes[$tr.attr('data-helanak-variation')] = { regular: $regular.val(), sale: $sale.val(), original: $tr.data('original') };
				}
			});
			$panel.find('[name="helanak_variation_prices"]').val(JSON.stringify(changes));
			return nativeSave.apply(this, arguments);
		};
	});
})(jQuery);
