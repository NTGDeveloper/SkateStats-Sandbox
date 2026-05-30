# SkateStats Sandbox Web Dashboard
A lightweight, modern PHP web frontend designed to consume and render [SkateStats]([url](https://github.com/NTGDeveloper/SkateStats)) JSON hockey feeds.
Built with a focus on simplicity and scannability, this dashboard transforms raw JSON game data into a clean, professional interface similar to standard collegiate and professional sports statistics platforms.
## Features
-   **Zero-Build Architecture:** No Node.js, npm, or complex build pipelines required. It runs on a single PHP file.
-   **Live Scoreboard:** Displays team logos, current score, period, and time remaining.
-   **Comprehensive Box Scores:** Fully sortable grid showing skater stats (Goals, Assists, Points, SOG, +/-, PIM, Faceoffs) and goalie metrics.
-   **Team Comparisons:** Side-by-side view of critical team metrics like Shots on Goal, Power Play Efficiency, and Penalty Minutes.
-   **Play-by-Play Timeline:** Chronological feed of events (goals, shots, faceoffs, penalties) color-coded for quick reading.
-   **Responsive Design:** Styled with Tailwind CSS via CDN, ensuring it looks great on both desktop and mobile devices.
## Prerequisites
Because this is a standard PHP script, it will run on virtually any standard web hosting environment:
-   PHP 7.4 or higher (PHP 8.x recommended)
-   A web server (LiteSpeed, Apache, Nginx, etc.)
-   Ensure `allow_url_fopen` is enabled in your `php.ini` (this is required for `file_get_contents` to fetch the external JSON feeds).
## Installation
1.  Clone this repository or download the source code.
2.  Upload the `index.php` file to your web server's public directory (e.g., `public_html`, `var/www/html`, or your specific domain's document root).
3.  Access the URL where you hosted the file (e.g., `[https://sandbox-skatestats.cgoldstein.xyz](https://sandbox-skatestats.cgoldstein.xyz)`).
## Usage
1.  Navigate to the web app in your browser.
2.  You will be greeted by a launch screen asking for a **JSON Feed URL**.
3.  Paste the URL of any valid SkateStats JSON file (e.g., an `example.json` endpoint).
4.  Click **Launch Dashboard**. The PHP script will parse the schema and generate the visual layout.
**Tip:** You can also bypass the launch screen by appending the feed URL directly as a GET parameter: `[https://sandbox-skatestats.cgoldstein.xyz/?feed_url=https://path.to/your/skatestats.json](https://sandbox-skatestats.cgoldstein.xyz/?feed_url=https://path.to/your/skatestats.json)`
## Technology Stack
-   **Backend / Routing:** PHP
-   **Frontend Styling:** [Tailwind CSS](https://tailwindcss.com/) (via CDN)
-   **Data Standard:** SkateStats Universal JSON Framework
## Vibecoded & Disclaimer
This project was **vibecoded** with **Gemini 3.1 Pro**. This software is provided "as is", without warranty of any kind. I assume no responsibility for any inaccuracies, bugs, downtime, or other liabilities that may arise from using this code in a production environment. Please review and test the code thoroughly before deploying it for critical use cases.
## License
This project is licensed under the **MIT License**. You are free to use, modify, distribute, and integrate this application into any open-source or proprietary closed-source platforms.
