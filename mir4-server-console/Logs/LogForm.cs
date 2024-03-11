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

        #region WorldLogsTree
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
        #endregion

        #region Ctrl-RightClick Hotkey
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
        #endregion

        #region WorldLogsList Menu (Right Click)
        private void WorldLogsList_MouseClick(object sender, MouseEventArgs e)
        {
            if (e.Button == MouseButtons.Right)
            {
                // Select the node that was clicked
                WorldLogsList.SelectedNode = WorldLogsList.GetNodeAt(e.X, e.Y);

                // If the clicked node is not null, show the context menu
                if (WorldLogsList.SelectedNode != null)
                {
                    // Create a context menu
                    ContextMenuStrip contextMenuStrip = new ContextMenuStrip();

                    // Add clickable options
                    ToolStripMenuItem Export = new ToolStripMenuItem("Export");
                    ToolStripMenuItem Delete = new ToolStripMenuItem("Delete");

                    // Attach event handlers for options
                    Export.Click += Export_Click;
                    Delete.Click += Delete_Click;

                    // Add options to the context menu
                    contextMenuStrip.Items.Add(Export);
                    contextMenuStrip.Items.Add(Delete);

                    // Show the context menu at the clicked location
                    contextMenuStrip.Show(WorldLogsList, e.Location);
                }
            }
        }
        private void Export_Click(object sender, EventArgs e)
        {
            // Check if a node is selected
            if (WorldLogsList.SelectedNode != null)
            {
                // Get the file name from the selected node
                string fileName = WorldLogsList.SelectedNode.Text;

                // Get the full path of the file
                string filePath = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "Data", fileName);

                // Check if the file exists
                if (File.Exists(filePath))
                {
                    // Prompt the user to choose the export location
                    SaveFileDialog saveFileDialog = new SaveFileDialog();
                    saveFileDialog.Filter = "JSON files (*.json)|*.json";
                    saveFileDialog.FileName = fileName;
                    saveFileDialog.Title = "Export JSON File";
                    saveFileDialog.InitialDirectory = Environment.GetFolderPath(Environment.SpecialFolder.Desktop);

                    // Show the SaveFileDialog
                    if (saveFileDialog.ShowDialog() == DialogResult.OK)
                    {
                        // Get the selected file path
                        string exportFilePath = saveFileDialog.FileName;

                        try
                        {
                            // Copy the file to the export location
                            File.Copy(filePath, exportFilePath);

                            MessageBox.Show($"File exported successfully to: {exportFilePath}", "Export Successful", MessageBoxButtons.OK, MessageBoxIcon.Information);
                        }
                        catch (Exception ex)
                        {
                            MessageBox.Show($"Error exporting file: {ex.Message}", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                        }
                    }
                }
                else
                {
                    MessageBox.Show($"File not found: {filePath}", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                }
            }
        }
        private void Delete_Click(object sender, EventArgs e)
        {
            // Check if a node is selected
            if (WorldLogsList.SelectedNode != null)
            {
                // Get the file name from the selected node
                string fileName = WorldLogsList.SelectedNode.Text;

                // Get the full path of the file
                string filePath = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "Data", fileName);

                // Check if the file exists
                if (File.Exists(filePath))
                {
                    // Prompt the user for confirmation
                    DialogResult result = MessageBox.Show($"Are you sure you want to delete '{fileName}'?", "Confirmation", MessageBoxButtons.YesNo, MessageBoxIcon.Question);

                    if (result == DialogResult.Yes)
                    {
                        try
                        {
                            // Delete the file
                            File.Delete(filePath);

                            // Remove the node from the TreeView
                            WorldLogsList.Nodes.Remove(WorldLogsList.SelectedNode);

                            MessageBox.Show($"File '{fileName}' deleted successfully.", "Success", MessageBoxButtons.OK, MessageBoxIcon.Information);
                        }
                        catch (Exception ex)
                        {
                            MessageBox.Show($"Error deleting file: {ex.Message}", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                        }
                    }
                }
                else
                {
                    MessageBox.Show($"File not found: {filePath}", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                }
            }
        }
        #endregion
    }
}