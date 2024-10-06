using Server_Console.Config;
using Server_Console.Database;
using Server_Console.Database_Tool;
using Server_Console.Logs;
using System.Data;
using System.Diagnostics;
using static System.Windows.Forms.VisualStyles.VisualStyleElement.Window;

namespace Server_Console
{
    public partial class MainForm : Form
    {
        private Point offset;
        public MainForm()
        {
            InitializeComponent();

            // Start a timer to periodically check the status every 5 seconds
            ExeCheckTimer.Interval = 5000; // 5 seconds
            ExeCheckTimer.Tick += ExeCheckTimer_Tick;
            ExeCheckTimer.Start();

            CheckServerStatus();

            HomePanel.BringToFront();

            LoadImages();
        }

        private void LoadImages()
        {
            FrontButton.Image = Properties.Resources.front;
            FrontButton.Tag = "front";
        }

        #region Server Checking //Done
        private void ExeCheckTimer_Tick(object sender, EventArgs e)
        {
            CheckServerStatus();
        }
        private void CheckServerStatus()
        {
            CheckExecutableStatus("Servers", "World", "WorldServer", WorldButton, Properties.Resources.world_, Properties.Resources.world);
            CheckExecutableStatus("Servers", "Gateway", "GatewayServer", GatewayButton, Properties.Resources.gateway_, Properties.Resources.gateway);
            CheckExecutableStatus("Servers", "Game", "GameServer", GameButton, Properties.Resources.game_, Properties.Resources.game);
            CheckExecutableStatus("Servers", "Front", "FrontServer", FrontButton, Properties.Resources.front_, Properties.Resources.front);
            CheckExecutableStatus("Servers", "Chatting", "ChattingServer", ChattingButton, Properties.Resources.chatting_, Properties.Resources.chatting);
        }

        private void CheckExecutableStatus(string serversBasePath, string serverFolder, string executableName, PictureBox pictureBox, Image runningImage, Image stoppedImage)
        {
            string baseDirectory = AppDomain.CurrentDomain.BaseDirectory;
            string serverPath = Path.Combine(baseDirectory, serversBasePath, serverFolder, $"{executableName}.exe");

            if (File.Exists(serverPath))
            {
                Process[] processes = Process.GetProcessesByName(executableName);

                if (processes.Length > 0)
                {
                    pictureBox.Image = runningImage;
                }
                else
                {
                    pictureBox.Image = stoppedImage;
                }
            }
            else
            {
                //MessageBox.Show($"{executableName}.exe not found.", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }
        #endregion

        #region Launching //Done
        private void LaunchServer(string serverExeName)
        {
            // Determine the server folder based on the serverExeName
            string serverFolder = "";

            switch (serverExeName)
            {
                case "WorldServer":
                    serverFolder = "World";
                    break;
                case "GatewayServer":
                    serverFolder = "Gateway";
                    break;
                case "GameServer":
                    serverFolder = "Game";
                    break;
                case "FrontServer":
                    serverFolder = "Front";
                    break;
                case "ChattingServer":
                    serverFolder = "Chatting";
                    break;
                default:
                    MessageBox.Show("Invalid server name.", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                    return;
            }

            string baseDirectory = AppDomain.CurrentDomain.BaseDirectory;
            string serverPath = Path.Combine(baseDirectory, "Servers", serverFolder, $"{serverExeName}.exe");

            if (File.Exists(serverPath))
            {
                Process[] processes = Process.GetProcessesByName(serverExeName);

                if (processes.Length > 0)
                {
                    // Server is already running, stop it
                    foreach (Process process in processes)
                    {
                        process.Kill(); // Terminate the process
                        process.WaitForExit(); // Wait for the process to exit
                    }
                    MessageBox.Show($"{serverExeName} server stopped.", "Server Stopped", MessageBoxButtons.OK, MessageBoxIcon.Information);
                }
                else
                {
                    // Server is not running, start it
                    LaunchExecutable(serverPath);
                }
            }
            else
            {
                MessageBox.Show($"{serverExeName}.exe not found.", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }

            // Update server status after launching or stopping the server
            CheckServerStatus();
        }
        private void LaunchExecutable(string exePath)
        {
            string exeDirectory = Path.GetDirectoryName(exePath);
            string exeFileName = Path.GetFileName(exePath);

            ProcessStartInfo startInfo = new ProcessStartInfo
            {
                FileName = exePath,
                WorkingDirectory = exeDirectory,
                WindowStyle = ProcessWindowStyle.Minimized
            };

            try
            {
                Process.Start(startInfo);
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Error launching {exeFileName}: {ex.Message}", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }
        #endregion

        #region CloseButton //Done
        private void CloseButton_Click(object sender, EventArgs e)
        {
            this.Close(); // Close the form
        }
        #endregion

        #region Server Launch Buttons //Done

        private void StartAllButton_Click(object sender, EventArgs e)
        {
            string baseDirectory = AppDomain.CurrentDomain.BaseDirectory;
            string batchFilePath = Path.Combine(baseDirectory, "Servers", "start_servers.bat");

            if (File.Exists(batchFilePath))
            {
                try
                {
                    ProcessStartInfo startInfo = new ProcessStartInfo(batchFilePath)
                    {
                        UseShellExecute = true,
                        WorkingDirectory = Path.GetDirectoryName(batchFilePath)
                    };
                    Process.Start(startInfo);
                }
                catch (Exception ex)
                {
                    MessageBox.Show($"Failed to start batch file. Error: {ex.Message}", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                }
            }
            else
            {
                MessageBox.Show($"Batch file not found at: {batchFilePath}", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void WorldButton_Click(object sender, EventArgs e)
        {
            LaunchServer("WorldServer");
        }

        private void GatewayButton_Click(object sender, EventArgs e)
        {
            LaunchServer("GatewayServer");
        }

        private void GameButton_Click(object sender, EventArgs e)
        {
            LaunchServer("GameServer");
        }

        private void FrontButton_Click(object sender, EventArgs e)
        {
            LaunchServer("FrontServer");
        }

        private void ChattingButton_Click(object sender, EventArgs e)
        {
            LaunchServer("ChattingServer");
        }
        #endregion

        #region Forms
        private void JSONButton_Click(object sender, EventArgs e) //Done
        {
            // Show JSONForm when JSONImage is clicked
            JSONForm jsonForm = new JSONForm();
            jsonForm.Show();
        }
        private void LogsButton_Click(object sender, EventArgs e) //Done
        {
            // Show LogForm when LogsImage is clicked
            LogForm logForm = new LogForm();
            logForm.Show();
        }
        private void DatabaseButton_Click(object sender, EventArgs e)
        {
            // Create an instance of DatabaseForm
            DatabaseForm databaseForm = new DatabaseForm();

            // Show the DatabaseForm
            databaseForm.Show();
        }
        private void ConfigButton_Click(object sender, EventArgs e) //Done
        {
            // Create an instance of ConfigForm
            ConfigForm configForm = new ConfigForm();

            // Show the ConfigForm
            configForm.Show();
        }
        private void SettingsButton_Click(object sender, EventArgs e)
        {
            // Create an instance of SettingsForm
            SettingsForm settingsForm = new SettingsForm();

            // Show the SettingsForm
            settingsForm.Show();
        }
        #endregion

        #region Logo Click //Done
        private void LOMCNLogo_Click(object sender, EventArgs e)
        {
            string url = "https://LOMCN.net/";
            Process.Start(new ProcessStartInfo(url) { UseShellExecute = true });
        }
        private void RZLogo_Click(object sender, EventArgs e)
        {
            string url = "https://forum.ragezone.com/";
            Process.Start(new ProcessStartInfo(url) { UseShellExecute = true });
        }
        #endregion

        #region Stop Servers Button //Done (Tweak batch)
        private void StopAllButton_Click(object sender, EventArgs e)
        {
            string baseDirectory = AppDomain.CurrentDomain.BaseDirectory;
            string batchFilePath = Path.Combine(baseDirectory, "Servers", "stop_servers.bat");

            if (File.Exists(batchFilePath))
            {
                try
                {
                    ProcessStartInfo startInfo = new ProcessStartInfo(batchFilePath)
                    {
                        UseShellExecute = true,
                        WorkingDirectory = Path.GetDirectoryName(batchFilePath)
                    };
                    Process.Start(startInfo);

                    // Show message that servers are closed
                    MessageBox.Show("Servers Closed", "Information", MessageBoxButtons.OK, MessageBoxIcon.Information);
                }
                catch (Exception ex)
                {
                    // Show error message in case of failure
                    MessageBox.Show($"Failed to stop servers. Error: {ex.Message}", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                }
            }
            else
            {
                // Show error message if the batch file is not found
                MessageBox.Show($"Batch file not found at: {batchFilePath}", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        #endregion

        #region Dragable Form
        private void MainForm_MouseDown(object sender, MouseEventArgs e)
        {
            if (e.Button == MouseButtons.Left)
            {
                // Capture the offset between the mouse cursor and the form's location
                offset = new Point(e.X, e.Y);
            }
        }

        private void MainForm_MouseMove(object sender, MouseEventArgs e)
        {
            if (e.Button == MouseButtons.Left)
            {
                // Calculate the new location of the form based on the offset
                Point newLocation = this.PointToScreen(new Point(e.X, e.Y));
                newLocation.Offset(-offset.X, -offset.Y);

                // Set the new location of the form
                this.Location = newLocation;
            }
        }
        #endregion

        #region TEMP DB BUTTONS
        private void MapsButton_Click(object sender, EventArgs e)
        {
            // Create an instance of the DatabaseTool form
            DatabaseTool databaseToolForm = new DatabaseTool();

            // Show the DatabaseTool form
            databaseToolForm.Show();
        }

        private void ItemsButton_Click(object sender, EventArgs e)
        {
            // Create an instance of the DatabaseTool form
            DatabaseTool databaseToolForm = new DatabaseTool();

            // Show the DatabaseTool form
            databaseToolForm.Show();
        }

        private void MonstersButton_Click(object sender, EventArgs e)
        {
            // Create an instance of the DatabaseTool form
            DatabaseTool databaseToolForm = new DatabaseTool();

            // Show the DatabaseTool form
            databaseToolForm.Show();
        }

        private void CommandsButton_Click(object sender, EventArgs e)
        {
            // Create an instance of the DatabaseTool form
            DatabaseTool databaseToolForm = new DatabaseTool();

            // Show the DatabaseTool form
            databaseToolForm.Show();
        }

        #endregion

        #region Button Hover Images

        #region Main
        private void StartAllButton_MouseEnter(object sender, EventArgs e)
        {
            StartAllButton.Image = Properties.Resources.Start_server_h;
        }

        private void StartAllButton_MouseLeave(object sender, EventArgs e)
        {
            StartAllButton.Image = Properties.Resources.Start_server;
        }

        private void StopAllButton_MouseEnter(object sender, EventArgs e)
        {
            StopAllButton.Image = Properties.Resources.stop_server_h;
        }

        private void StopAllButton_MouseLeave(object sender, EventArgs e)
        {
            StopAllButton.Image = Properties.Resources.stop_server;
        }

        private void ConfigButton_MouseEnter(object sender, EventArgs e)
        {
            ConfigButton.Image = Properties.Resources.config_h;
        }

        private void ConfigButton_MouseLeave(object sender, EventArgs e)
        {
            ConfigButton.Image = Properties.Resources.config;
        }

        private void LogsButton_MouseEnter(object sender, EventArgs e)
        {
            LogsButton.Image = Properties.Resources.log_h;
        }

        private void LogsButton_MouseLeave(object sender, EventArgs e)
        {
            LogsButton.Image = Properties.Resources.log;
        }

        private void JSONButton_MouseEnter(object sender, EventArgs e)
        {
            JSONButton.Image = Properties.Resources.json_h;
        }

        private void JSONButton_MouseLeave(object sender, EventArgs e)
        {
            JSONButton.Image = Properties.Resources.json;
        }

        private void DatabaseButton_MouseEnter(object sender, EventArgs e)
        {
            DatabaseButton.Image = Properties.Resources.sql_h;
        }

        private void DatabaseButton_MouseLeave(object sender, EventArgs e)
        {
            DatabaseButton.Image = Properties.Resources.sql_;
        }
        #endregion

        #region Database
        private void MapsButton_MouseEnter(object sender, EventArgs e)
        {
            MapsButton.Image = Properties.Resources.maps_h;
        }

        private void MapsButton_MouseLeave(object sender, EventArgs e)
        {
            MapsButton.Image = Properties.Resources.maps;
        }

        private void ItemsButton_MouseEnter(object sender, EventArgs e)
        {
            ItemsButton.Image = Properties.Resources.items_h;
        }

        private void ItemsButton_MouseLeave(object sender, EventArgs e)
        {
            ItemsButton.Image = Properties.Resources.items;
        }

        private void MonstersButton_MouseEnter(object sender, EventArgs e)
        {
            MonstersButton.Image = Properties.Resources.monsters_h;
        }

        private void MonstersButton_MouseLeave(object sender, EventArgs e)
        {
            MonstersButton.Image = Properties.Resources.monsters;
        }

        private void CommandsButton_MouseEnter(object sender, EventArgs e)
        {
            CommandsButton.Image = Properties.Resources.commands_h;
        }

        private void CommandsButton_MouseLeave(object sender, EventArgs e)
        {
            CommandsButton.Image = Properties.Resources.commands;
        }
        #endregion

        #region Settings
        private void SettingsButton_MouseEnter(object sender, EventArgs e)
        {
            SettingsButton.Image = Properties.Resources.settings_h;
        }

        private void SettingsButton_MouseLeave(object sender, EventArgs e)
        {
            SettingsButton.Image = Properties.Resources.settings;
        }
        #endregion

        #region Servers
        private void FrontButton_MouseEnter(object sender, EventArgs e)
        {
            // Change to highlighted versions on MouseEnter
            if (FrontButton.Image == Properties.Resources.front)
            {
                FrontButton.Image = Properties.Resources.front__h;
            }
            else if (FrontButton.Image == Properties.Resources.front_)
            {
                FrontButton.Image = Properties.Resources.front_h;
            }
        }

        private void FrontButton_MouseLeave(object sender, EventArgs e)
        {
            // Revert to the original versions on MouseLeave
            if (FrontButton.Image == Properties.Resources.front__h)
            {
                FrontButton.Image = Properties.Resources.front;
            }
            else if (FrontButton.Image == Properties.Resources.front_h)
            {
                FrontButton.Image = Properties.Resources.front_;
            }
        }

        private void GameButton_MouseEnter(object sender, EventArgs e)
        {
            // Change to highlighted versions on MouseEnter
            if (GameButton.Image == Properties.Resources.game)
            {
                GameButton.Image = Properties.Resources.game__h;
            }
            else if (GameButton.Image == Properties.Resources.game_)
            {
                GameButton.Image = Properties.Resources.game_h;
            }
        }

        private void GameButton_MouseLeave(object sender, EventArgs e)
        {
            // Revert to the original versions on MouseLeave
            if (GameButton.Image == Properties.Resources.game__h)
            {
                GameButton.Image = Properties.Resources.game;
            }
            else if (GameButton.Image == Properties.Resources.game_h)
            {
                GameButton.Image = Properties.Resources.game_;
            }
        }

        private void GatewayButton_MouseEnter(object sender, EventArgs e)
        {

        }

        private void GatewayButton_MouseLeave(object sender, EventArgs e)
        {

        }

        private void ChattingButton_MouseEnter(object sender, EventArgs e)
        {

        }

        private void ChattingButton_MouseLeave(object sender, EventArgs e)
        {

        }

        private void WorldButton_MouseEnter(object sender, EventArgs e)
        {

        }

        private void WorldButton_MouseLeave(object sender, EventArgs e)
        {

        }
        #endregion

        #endregion

    }
}