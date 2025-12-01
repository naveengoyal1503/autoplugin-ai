# Auto Plugin Factory

Generate WordPress plugins automatically and sync them to Notion + GitHub.

## What users can do on the site
- Trigger automatic WordPress plugin generation (AI-driven) and get a full plugin + README.
- Download each plugin safely as a ZIP (PHP + README) via the protected tracker link.
- View plugin details (name, description, category, target users, monetization) synced to Notion.
- Rely on fresh builds even if one AI provider fails, thanks to Perplexity → Gemini fallback.
- Track provenance: every plugin is stored in this repo under `plugins/<PluginName>/` with history.

## What it does
- Polls Perplexity (with Gemini fallback) to generate a full WordPress plugin + README for each run.
- Saves artifacts to `generated-plugins/_protected_files` on the server.
- Syncs metadata to Notion.
- Commits each generated plugin (and its README) to this repo under `plugins/<PluginName>/` and pushes to `main`.

## Architecture
- `cron.php`: core factory script (prompting, JSON cleanup, file save, Notion + GitHub sync).
- `generated-plugins/tracker.php`: protected download endpoint that zips the plugin + README and stamps Author metadata.
- `notion-hook.php`: receives payloads from the factory and writes files + Notion records.
- `plugins/`: repo folder where each generated plugin is committed.

## Running locally/cron
Example cron every 2 minutes:
```
*/2 * * * * GITHUB_TOKEN=ghp_xxx /usr/bin/php -q /home/u676948462/domains/automation.bhandarum.in/cron.php >/dev/null 2>&1
```
Environment vars used:
- `GITHUB_TOKEN`: PAT with `repo` access (for pushes).
- `PPLX_API_KEY`: Perplexity API key.
- `GEMINI_API_KEY`: Gemini key (fallback).
- `GEMINI_MODEL_TEXT` / `GEMINI_MODEL_FALLBACKS`: optional model overrides.

## GitHub sync workflow
1) Factory generates plugin + README.  
2) Files are copied into `plugins/<PluginName>/` as `<PluginName>.php` and `README.md`.  
3) Changes are committed on `main` with message `Add plugin <PluginName>`.  
4) Push to `origin main` using the provided `GITHUB_TOKEN`.

## Logs
- `plugin_factory_log.txt`: generation, Notion, and GitHub sync status.
- `plugin_factory_raw.txt`: raw model response for debugging JSON issues.

## Notes
- Author metadata is enforced as `Author: Auto Plugin Factory` and `Author URI: <tracker-link>` on every download.
- Protected downloads include both plugin PHP and README in a zip via the tracker.

## Developer
Developed and managed by [Naveen Goyal](https://naveen.bhandarum.in)
