# Installed design and review tools

Installed for the Helanak work on 28 October 2026. These are development-agent
skills, not WordPress plugins. Selection used official maintainers and relevant
upstream projects; there is no universal cross-directory "top rated" ranking.
Installing a skill does not mean every technique should be added to the site.

| Source | Installed skills / reference |
| --- | --- |
| [UI UX Pro Max](https://github.com/nextlevelbuilder/ui-ux-pro-max-skill) | `ui-ux-pro-max`; accompanying `banner-design`, `brand`, `design`, `design-system`, `slides`, `ui-styling` |
| [Three.js skills](https://github.com/CloudAI-X/threejs-skills) | `threejs-animation`, `threejs-asset-pipeline`, `threejs-fundamentals`, `threejs-geometry`, `threejs-interaction`, `threejs-lighting`, `threejs-loaders`, `threejs-materials`, `threejs-performance`, `threejs-postprocessing`, `threejs-react-three-fiber`, `threejs-shaders`, `threejs-textures` |
| [WordPress agent skills](https://github.com/WordPress/agent-skills) | `wordpress-router`, `wp-project-triage`, `wp-plugin-development`, `wp-performance`, `wp-phpstan`, `wp-wpcli-and-ops`, `wp-rest-api`, `wpds` |
| [Vercel agent skills](https://github.com/vercel-labs/agent-skills) | `web-design-guidelines` |
| [OpenAI skills](https://github.com/openai/skills) | `chatgpt-apps`, `playwright`, `security-best-practices`, `security-threat-model` |
| [Web quality skills](https://github.com/addyosmani/web-quality-skills) | `accessibility`, `best-practices`, `core-web-vitals`, `performance`, `seo`, `web-quality-audit` |
| [OpenAI Apps SDK UI](https://github.com/openai/apps-sdk-ui) | Version `0.2.2` package staged as an official UI reference |

The Pro Max package and its companion skills are available in the repository's
`.agents/skills/`. Other installed skills are under
`C:\Users\Mato\.codex\skills`. The OpenAI UI package is staged privately at
`.local/tools/openai-apps-sdk-ui-0.2.2.tgz`.

The existing `frontend-design` and `agent-browser` skills supplied the design
implementation/review workflow and browser testing. WordPress operations and
plugin guidance supplied migration, dependency, security, and architecture
checks. Three.js and the React-oriented OpenAI kit are available for future
work; the current lightweight SVG character system was preserved and extended
without adding a Three.js/React runtime dependency.
