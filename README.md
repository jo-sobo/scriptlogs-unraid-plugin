# Scriptlogs for Unraid

> A dashboard widget to monitor your User Scripts in real-time.

This plugin adds a configurable widget to the Unraid Dashboard, allowing you to see the status and log output of your favorite scripts without needing to keep the User Scripts page open.

![Scriptlogs Widget Screenshot](https://raw.githubusercontent.com/jo-sobo/scriptlogs-unraid-plugin/main/scriptlogs_screenshot.png)

## ✨ Features

* Displays a compact, movable widget on your Unraid Dashboard.
* Color-coded Buttons for running scripts (Green for running, Gray for idle).
* View live log output for scripts running in the background directly in the widget.
* "Smart scrolling" feature: The log view preserves your scroll position when you scroll up and resumes auto-scrolling when you scroll back to the bottom.
* Manage the monitored scripts and update rate on the the Settings page.

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
    * **Enable/Disable Automatic Refresh:** Turn the live update feature on or off.
    * **Set the Refresh Interval:** Define how often the widget should update (in seconds).
    * **Select Your Scripts:** Use the checkboxes to choose which scripts you want to monitor on your dashboard.
4.  Click **Apply** to save your changes. The widget on the dashboard will update accordingly.

## 📝 How it Works

The widget periodically polls a backend API file. This API checks the system's process list (`ps -ef`) to determine if a script is running and how it was launched (`startScript.sh` for foreground or `startBackground.php` for background). Live logs for background scripts are read directly from the temporary `log.txt` files created by the User Scripts plugin.


## 🙏 Acknowledgments

A big thank you to **Andrew Zawadzki** for creating and maintaining the excellent User Scripts plugin.

## License
This project is licensed under the GPL v3 License - see the [LICENSE](LICENSE) file for details.
