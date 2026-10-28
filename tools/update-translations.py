"""Merge extracted source catalogs, preserve translations, and compile Persian MO files."""

import json
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1] / ".local/tools/python"))
import polib


def main():
    root = Path(__file__).resolve().parents[1]
    messages = json.loads((root / "tools/translations-fa.json").read_text(encoding="utf-8"))
    messages.update({
        "WP Helanak Kids Theme": "پوسته کودکانه هلانک",
        "Helanak Core": "هسته هلانک",
        "Helanak": "هلانک",
        "https://helanak.com": "https://helanak.com",
        "Classic WooCommerce theme for Helanak Kids with Persian/English layouts, seasonal colors, accessible shopping interactions, and animated animal companions. Store data and rules are provided by the Helanak Core plugin.": "پوسته ووکامرس هلانک با چیدمان فارسی و انگلیسی، رنگ‌های فصلی، امکانات خرید دسترس‌پذیر و شخصیت‌های حیوانی متحرک. داده‌ها و قوانین فروشگاه توسط افزونه هسته هلانک ارائه می‌شود.",
        "Store data and rules for Helanak Kids: age ranges, product safety, badges, languages, shipping eligibility, store forms, image optimization, and WooCommerce HPOS compatibility. Storefront presentation belongs to the Helanak theme.": "داده‌ها و قوانین فروشگاه هلانک: رده‌های سنی، ایمنی محصولات، نشان‌ها، زبان‌ها، شرایط ارسال، فرم‌های فروشگاه، بهینه‌سازی تصاویر و سازگاری با HPOS ووکامرس. نمایش فروشگاه در پوسته هلانک انجام می‌شود.",
        "Please wait a few minutes before sending another message.": "لطفاً چند دقیقه صبر کنید و سپس پیام دیگری بفرستید.",
        "Your message could not be sent. Your details are saved below; please try again later.": "پیام شما ارسال نشد. اطلاعات شما در فرم زیر حفظ شده است؛ لطفاً بعداً دوباره تلاش کنید.",
        "Free shipping progress": "پیشرفت تا ارسال رایگان",
        "%d min read": "%d دقیقه مطالعه",
        "%d item": "%d محصول",
        "About": "درباره ما",
        "Contact": "تماس با ما",
        "Sale / Campaign": "فروش ویژه / کمپین",
        "you@example.com": "you@example.com",
        "Enter your name using no more than 120 characters.": "نام خود را با حداکثر ۱۲۰ نویسه وارد کنید.",
        "Enter a valid email address.": "یک نشانی ایمیل معتبر وارد کنید.",
        "Keep the subject within 200 characters.": "موضوع را در حداکثر ۲۰۰ نویسه بنویسید.",
        "Enter a message using no more than 10,000 characters.": "پیام خود را با حداکثر ۱۰٬۰۰۰ نویسه وارد کنید.",
        "New contact form message": "پیام جدید از فرم تماس",
        "English navigation label": "عنوان منو به انگلیسی",
        "Persian navigation label": "عنوان منو به فارسی",
        "Activate Helanak Core 0.3.0 or later for store forms, shipping progress, product safety, badges, and age filters. Storefront pages remain available when the companion plugin is missing.": "برای فرم‌های فروشگاه، پیشرفت ارسال رایگان، ایمنی محصولات، نشان‌ها و فیلتر سن، نسخه ۰٫۳٫۰ یا بالاتر افزونه هسته هلانک را فعال کنید. در نبود این افزونه، صفحه‌های فروشگاه همچنان نمایش داده می‌شوند.",
    })
    pairs = [
        ("wp-helanak-kids-theme/languages/wp-helanak-kids-theme.pot", "wp-helanak-kids-theme/languages/fa_IR.po"),
        ("helanak-core-plugin/languages/helanak-core.pot", "helanak-core-plugin/languages/helanak-core-fa_IR.po"),
    ]
    catalogs = []
    missing = []
    for source, target in pairs:
        po = polib.pofile(str(root / target))
        po.merge(polib.pofile(str(root / source)))
        for entry in po:
            if entry.obsolete:
                continue
            translated = all(entry.msgstr_plural.get(i) for i in (0, 1)) if entry.msgid_plural else bool(entry.msgstr)
            if not translated:
                if entry.msgid not in messages:
                    missing.append(entry.msgid)
                    continue
                if entry.msgid_plural:
                    entry.msgstr_plural = {0: messages[entry.msgid], 1: messages[entry.msgid]}
                else:
                    entry.msgstr = messages[entry.msgid]
                if "fuzzy" in entry.flags:
                    entry.flags.remove("fuzzy")
        catalogs.append((target, po))
    if missing:
        print(json.dumps({"missing_translations": missing}, ensure_ascii=False, indent=2))
        raise SystemExit(1)
    for target, po in catalogs:
        po.save(str(root / target))
        po.save_as_mofile(str((root / target).with_suffix(".mo")))
        print(json.dumps({"catalog": target, "translated_entries": len(po.translated_entries())}))


if __name__ == "__main__":
    main()
