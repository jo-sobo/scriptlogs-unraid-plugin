### 2026.06.11
- Fixed light-theme support so the dashboard tile and settings page actually follow the active Unraid theme; the previous theme overrides relied on body classes that no Unraid version sets.
- Switched theme handling to server-side detection of the Unraid theme with native webGui CSS variables on Unraid 7.1+ and tuned fallback palettes for older versions.
- Fixed unreadable gray settings text and the dark-only script selection panel, dashboard tabs, and log view in light themes.

### 2026.06.10
- Added light-theme support to the settings page.
- Refined settings script reordering to drag only from the handle and suppress drop snap-back.
- Preserved dashboard tab focus and running animations across refreshes.
- Removed superseded plugin packages during install.
- Fixed the settings script selection layout on Unraid by switching the reorder UI to a compact list.
- Added custom ordering for selected scripts from the settings page.
- Fixed the settings Apply button flow on Unraid settings pages.
- Fixed package archive ownership so installs do not alter parent system directory owners.
- Added package MD5 checksums to plugin metadata and rebuilt package archives with root-owned entries.
- Added shipped default configuration and centralized shared defaults, font sizes, tail limits, and JSON options.
- Improved dashboard polling to avoid overlapping requests and refresh correctly from an initially empty script selection.
- Added light-theme-aware dashboard styling, keyboard-accessible tabs, and initial collapsed-state synchronization.
- Hardened log tailing, truncation reporting, foreground script detection, and JSON output.
- Improved settings saves with explicit CSRF token handling, atomic config writes, and saved-value refresh after Apply.
- Preserved user settings on uninstall while still removing downloaded package archives.

### 2026.06.09
- Fixed dashboard widget overflow when many scripts are selected.
- Added a contained script tab scroller with a separate scrollbar lane.
- Refined selected and running script indicators.
- Improved log display behavior, settings layout, README screenshots, and plugin metadata.

### 2026.03.08
- Added shared script list encoding helpers.
- Improved log autoscroll behavior.
- Refined dashboard and settings layout.

### 2025.11.05
- Initial public ScriptLogs release.
