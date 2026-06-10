### 2026.06.10
- Fixed the settings Apply button flow on Unraid settings pages.
- Fixed package archive ownership so installs do not alter parent system directory owners.
- Added package MD5 checksums to plugin metadata and rebuilt package archives with root-owned entries.
- Added shipped default configuration and centralized shared defaults, font sizes, tail limits, and JSON options.
- Improved dashboard polling to avoid overlapping requests and refresh correctly from an initially empty script selection.
- Added light-theme-aware dashboard styling, keyboard-accessible tabs, and initial collapsed-state synchronization.
- Hardened log tailing, truncation reporting, foreground script detection, and JSON output.
- Improved settings saves with explicit CSRF token handling, atomic config writes, and redirect-after-save.
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
- Initial public Scriptlogs release.
