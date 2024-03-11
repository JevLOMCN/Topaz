using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Data;
using System.Diagnostics;
using System.Drawing;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using System.Windows.Forms;

namespace Server_Console.Logs
{
    public partial class LogForm : Form
    {
        private LogSearch logSearchForm;
        public LogForm()
        {
            InitializeComponent();

            PopulateLogsTree("WorldServer");
            PopulateLogsTree("GatewayServer");
            PopulateLogsTree("GameServer");
            PopulateLogsTree("FrontServer");
            PopulateLogsTree("ChattingServer");

            WorldLogsList.AfterSelect += LogsList_AfterSelect;
            GatewayLogsList.AfterSelect += LogsList_AfterSelect;
            GameLogsList.AfterSelect += LogsList_AfterSelect;
            FrontLogsList.AfterSelect += LogsList_AfterSelect;
            ChattingLogsList.AfterSelect += LogsList_AfterSelect;

        }

        private void PopulateLogsTree(string serverName)
        {
            string baseDirectory = AppDomain.CurrentDomain.BaseDirectory;
            string logsDirectory;

            // Determine the logs directory based on the server name
            switch (serverName)
            {
                case "WorldServer":
                    logsDirectory = Path.Combine(baseDirectory, "Servers", "World", "logs", "WorldServer");
                    break;
                case "GatewayServer":
                    logsDirectory = Path.Combine(baseDirectory, "Servers", "Gateway", "logs", "GatewayServer");
                    break;
                case "GameServer":
                    logsDirectory = Path.Combine(baseDirectory, "Servers", "Game", "logs", "GameServer");
                    break;
                case "FrontServer":
                    logsDirectory = Path.Combine(baseDirectory, "Servers", "Front", "logs");
                    break;
                case "ChattingServer":
                    logsDirectory = Path.Combine(baseDirectory, "Servers", "Chatting", "logs", "ChattingServer");
                    break;
                default:
                    MessageBox.Show($"Invalid server name: {serverName}", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                    return;
            }

            try
            {
                string[] files;
                if (serverName == "FrontServer")
                {
                    // For Front Server, use "*.txt" as the search pattern
                    files = Directory.GetFiles(logsDirectory, "*.txt");
                }
                else
                {
                    // For other servers, use "*.log" as the search pattern
                    files = Directory.GetFiles(logsDirectory, "*.log");
                }

                // Add files to the respective TreeView based on the server name
                switch (serverName)
                {
                    case "WorldServer":
                        AddFilesToTree(files, WorldLogsList.Nodes);
                        break;
                    case "GatewayServer":
                        AddFilesToTree(files, GatewayLogsList.Nodes);
                        break;
                    case "GameServer":
                        AddFilesToTree(files, GameLogsList.Nodes);
                        break;
                    case "FrontServer":
                        AddFilesToTree(files, FrontLogsList.Nodes);
                        break;
                    case "ChattingServer":
                        AddFilesToTree(files, ChattingLogsList.Nodes);
                        break;
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Error loading {serverName} logs: {ex.Message}", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }



        private void AddFilesToTree(string[] files, TreeNodeCollection nodes)
        {
            foreach (string file in files)
            {
                TreeNode node = new TreeNode(Path.GetFileName(file));
                nodes.Add(node);
            }
        }

        private void LogsList_AfterSelect(object sender, TreeViewEventArgs e)
        {
            TreeView treeView = sender as TreeView;
            if (treeView != null && treeView.SelectedNode != null)
            {
                string selectedFile = treeView.SelectedNode.Text;
                string serverName = treeView.Name.Replace("LogsList", "") + "Server"; // Append "Server" to the server name

                // Construct the correct logs directory path based on the server name
                string baseDirectory = AppDomain.CurrentDomain.BaseDirectory;
                string logsDirectory;
                switch (serverName)
                {
                    case "WorldServer":
                        logsDirectory = Path.Combine(baseDirectory, "Servers", "World", "logs", "WorldServer");
                        break;
                    case "GatewayServer":
                        logsDirectory = Path.Combine(baseDirectory, "Servers", "Gateway", "logs", "GatewayServer");
                        break;
                    case "GameServer":
                        logsDirectory = Path.Combine(baseDirectory, "Servers", "Game", "logs", "GameServer");
                        break;
                    case "FrontServer":
                        logsDirectory = Path.Combine(baseDirectory, "Servers", "Front", "logs");
                        break;
                    case "ChattingServer":
                        logsDirectory = Path.Combine(baseDirectory, "Servers", "Chatting", "logs", "ChattingServer");
                        break;
                    default:
                        MessageBox.Show($"Invalid server name: {serverName}", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                        return;
                }

                // Append the selected file to the logs directory path
                string logFilePath = Path.Combine(logsDirectory, selectedFile);

                // Read the contents of the log file
                try
                {
                    string logContents = File.ReadAllText(logFilePath);

                    // Get the corresponding RichTextBox based on the server name
                    RichTextBox serverBox = null;
                    switch (serverName)
                    {
                        case "WorldServer":
                            serverBox = WorldBox;
                            break;
                        case "GatewayServer":
                            serverBox = GatewayBox;
                            break;
                        case "GameServer":
                            serverBox = GameBox;
                            break;
                        case "FrontServer":
                            serverBox = FrontBox;
                            break;
                        case "ChattingServer":
                            serverBox = ChattingBox;
                            break;
                    }

                    // Display the log contents in the corresponding RichTextBox
                    if (serverBox != null)
                    {
                        serverBox.Text = logContents;
                    }
                }
                catch (Exception ex)
                {
                    MessageBox.Show($"Error loading log file: {ex.Message}", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                }
            }
        }
        protected override bool ProcessCmdKey(ref Message msg, Keys keyData)
        {
            // Check if Ctrl+F is pressed
            if (keyData == (Keys.Control | Keys.F))
            {
                // Create an instance of LogSearch form if not already created
                if (logSearchForm == null || logSearchForm.IsDisposed)
                {
                    logSearchForm = new LogSearch(this);
                }

                // Show the LogSearch form
                logSearchForm.Show();
                logSearchForm.BringToFront(); // Bring the form to front if already open
                return true; // Mark the shortcut as handled
            }

            return base.ProcessCmdKey(ref msg, keyData);
        }
    }
}