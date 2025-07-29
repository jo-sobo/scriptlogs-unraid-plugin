# Scriptlogs for Unraid

> A dashboard widget to monitor your User Scripts in real-time.

This plugin adds a configurable widget to the Unraid Dashboard, allowing you to see the status and log output of your favorite scripts without needing to keep the User Scripts page open.

![Scriptlogs Widget Screenshot running script](https://raw.githubusercontent.com/jo-sobo/scriptlogs-unraid-plugin/main/scriptlogs_screenshot_running.png)
![Scriptlogs Widget Screenshot idle script](https://raw.githubusercontent.com/jo-sobo/scriptlogs-unraid-plugin/main/scriptlogs_screenshot_idle.png)

## ✨ Features

* Displays a compact, movable widget on your Unraid Dashboard.
* Shows the real-time status of selected User Scripts with color-coded tabs (**Green** for running, **Gray** for idle).
* Differentiates between **foreground scripts** (showing a notice to check the User Scripts window) and **background scripts** (showing a live log).
* Optionally displays the last known log for idle scripts (from their last background run).
* **"Smart scrolling"** feature: The log view preserves your scroll position when you scroll up and resumes auto-scrolling when you scroll back to the bottom.
* Fully configurable via a dedicated settings page.

## Prerequisites

This plugin **requires** the **[User Scripts](https://forums.unraid.net/topic/48286-plugin-user-scripts/)** plugin by Andrew Zawadzki to be installed and functional.

## 💾 Installation

1.  In the Unraid web interface, go to the **Plugins** tab.
2.  Click on **Install Plugin**.
3.  Paste the following URL into the text box and click **Install**:

    ```
    https://raw.githubusercontent.com/jo-sobo/scriptlogs-unraid-plugin/main/scriptlogs.plg
    ```

## ⚙️ Configuration

After installation, you can configure the widget to your needs.

1.  Go to the **Utilities** tab in the Unraid web interface.
2.  Click on **Scriptlogs Settings**.
3.  From here you can:
    * **Automatic Refresh:** Turn the live update feature on or off.
    * **Refresh Interval:** Define how often the widget should update (in seconds).
    * **Show Last Log for Idle Scripts:** If enabled, the widget will display the last saved log for any idle script that was previously run **in the background**.
    * **Script Selection:** Use the checkboxes to choose which scripts you want to monitor on your dashboard.
4.  Click **Apply** to save your changes. The widget on the dashboard will update accordingly.

## 📝 How it Works

The widget periodically polls a backend API file. This API uses a hybrid approach to determine script status:
1.  It checks the system's process list (`ps -ef`) for scripts running in the **foreground** (via `startScript.sh`).
2.  It checks for a status file in `/tmp/user.scripts/running/` for scripts running in the **background**.

Live logs are read from the temporary `log.txt` files created by the User Scripts plugin in `/tmp/user.scripts/tmpScripts/`.

## 🙏 Acknowledgments

A big thank you to **Andrew Zawadzki** for creating and maintaining the excellent User Scripts plugin.

## License
This project is licensed under the GPL v3 License - see the [LICENSE](LICENSE) file for details.