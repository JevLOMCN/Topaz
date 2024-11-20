using Newtonsoft.Json;
using Server_Console.JSON;
using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Data;
using System.Drawing;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using System.Windows.Forms;

namespace Server_Console
{
    public partial class JSONForm : Form
    {
        private string directoryPath;
        public JSONForm()
        {
            InitializeComponent();
            PopulateTreeView();
        }

        #region JSON Load
        private void PopulateTreeView()
        {
            // Clear existing nodes
            FileList.Nodes.Clear();

            // Get the directory path
            string directoryPath = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "Servers", "Data");

            // Check if the directory exists
            if (Directory.Exists(directoryPath))
            {
                // Get the list of JSON files in the directory
                string[] jsonFiles = Directory.GetFiles(directoryPath, "*.json");

                // Add each JSON file name as a node in the TreeView
                foreach (string file in jsonFiles)
                {
                    string fileName = Path.GetFileName(file);
                    TreeNode node = new TreeNode(fileName);
                    FileList.Nodes.Add(node);
                }
            }
            else
            {
                // Directory doesn't exist, display a message
                MessageBox.Show("Data directory not found.", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }
        private void FileList_AfterSelect(object sender, TreeViewEventArgs e)
        {
            // Clear existing columns
            JsonDataGrid.Columns.Clear();

            if (e.Node == null)
                return;

            string fileName = e.Node.Text;
            string filePath = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "Servers", "Data", fileName);

            if (Path.GetExtension(filePath) == ".json")
            {
                LoadJsonData(filePath);
            }
        }
        private void LoadJsonData(string filePath)
        {
            try
            {
                string json = File.ReadAllText(filePath);

                List<Dictionary<string, object>> data = JsonConvert.DeserializeObject<List<Dictionary<string, object>>>(json);

                // Clear existing columns
                JsonDataGrid.Columns.Clear();

                // Add columns dynamically based on keys in the JSON data
                foreach (var item in data)
                {
                    foreach (var key in item.Keys)
                    {
                        if (!JsonDataGrid.Columns.Contains(key))
                        {
                            JsonDataGrid.Columns.Add(key, key);
                        }
                    }
                }

                // Populate rows
                foreach (var item in data)
                {
                    List<string> row = new List<string>();
                    foreach (var key in JsonDataGrid.Columns)
                    {
                        if (item.ContainsKey(key.ToString()))
                        {
                            row.Add(item[key.ToString()].ToString());
                        }
                        else
                        {
                            row.Add("");
                        }
                    }
                    JsonDataGrid.Rows.Add(row.ToArray());
                }
            }
            catch (Exception ex)
            {
                //MessageBox.Show("Error loading JSON file: " + ex.Message);
            }
        }

        #endregion

        #region FileList Menu (Right Click)
        private void FileList_MouseClick(object sender, MouseEventArgs e)
        {
            if (e.Button == MouseButtons.Right)
            {
                // Select the node that was clicked
                FileList.SelectedNode = FileList.GetNodeAt(e.X, e.Y);

                // If the clicked node is not null, show the context menu
                if (FileList.SelectedNode != null)
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
                    contextMenuStrip.Show(FileList, e.Location);
                }
            }
        }
        private void Export_Click(object sender, EventArgs e)
        {
            // Check if a node is selected
            if (FileList.SelectedNode != null)
            {
                // Get the file name from the selected node
                string fileName = FileList.SelectedNode.Text;

                // Get the full path of the file
                string filePath = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "Servers", "Data", fileName);

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
            if (FileList.SelectedNode != null)
            {
                // Get the file name from the selected node
                string fileName = FileList.SelectedNode.Text;

                // Get the full path of the file
                string filePath = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "Servers", "Data", fileName);

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
                            FileList.Nodes.Remove(FileList.SelectedNode);

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

        #region Export All Button
        private void ExportJSONsToolStripMenuItem_Click(object sender, EventArgs e)
        {
            // Define the source folder path
            string sourceFolderPath = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "Servers", "Data");

            // Create and configure a FolderBrowserDialog
            using (FolderBrowserDialog folderBrowserDialog = new FolderBrowserDialog())
            {
                folderBrowserDialog.Description = "Select the destination folder to save the copy of Data";
                folderBrowserDialog.ShowNewFolderButton = true;

                // Show the dialog and check if the user selected a folder
                if (folderBrowserDialog.ShowDialog() == DialogResult.OK)
                {
                    string destinationFolderPath = folderBrowserDialog.SelectedPath;

                    try
                    {
                        // Copy the Data folder to the selected destination
                        CopyDirectory(sourceFolderPath, destinationFolderPath);

                        // Notify the user that the copy was successful
                        MessageBox.Show("Folder copied successfully!", "Success", MessageBoxButtons.OK, MessageBoxIcon.Information);
                    }
                    catch (Exception ex)
                    {
                        // Handle any errors that occur during the copy process
                        MessageBox.Show($"An error occurred: {ex.Message}", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                    }
                }
            }
        }

        // Method to copy all files and subdirectories from one directory to another
        private void CopyDirectory(string sourceDir, string destDir)
        {
            // Create the destination directory if it doesn't exist
            if (!Directory.Exists(destDir))
            {
                Directory.CreateDirectory(destDir);
            }

            // Get the files in the source directory and copy them to the new location
            foreach (string file in Directory.GetFiles(sourceDir))
            {
                string destFile = Path.Combine(destDir, Path.GetFileName(file));
                File.Copy(file, destFile, true);
            }

            // Copy subdirectories and their contents to new location
            foreach (string subDir in Directory.GetDirectories(sourceDir))
            {
                string destSubDir = Path.Combine(destDir, Path.GetFileName(subDir));
                CopyDirectory(subDir, destSubDir);
            }
        }
        #endregion

        private void respawnsToolStripMenuItem_Click(object sender, EventArgs e)
        {
            RespawnForm respawnForm = new RespawnForm();
            respawnForm.Show();
            this.Close();
        }
    }
}