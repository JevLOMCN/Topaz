using System.Diagnostics;

namespace Mir_4_Client_Editor
{
    public partial class MainForm : Form
    {
        public MainForm()
        {
            InitializeComponent();
        }
        private void MainForm_Load(object sender, EventArgs e)
        {
            this.Size = new Size(280, 100);
            EditGroup.Visible = false;
        }

        #region Export
        private void ExportToolStripMenuItem_Click(object sender, EventArgs e)
        {
            EditGroup.Visible = false;

            this.Size = new Size(280, 100);


            OpenFileDialog openFileDialog1 = new OpenFileDialog
            {
                InitialDirectory = Directory.GetCurrentDirectory(),
                Filter = "PAK files (*.pak)|*.pak",
                FilterIndex = 2,
                RestoreDirectory = true
            };

            if (openFileDialog1.ShowDialog() == DialogResult.OK)
            {
                try
                {
                    // Jev - Placeholer
                    string selectedFilePath = openFileDialog1.FileName;
                }
                catch (Exception ex)
                {
                    MessageBox.Show("Error: Could not open PAK file. Original error: " + ex.Message);
                }
            }
        }
        #endregion

        #region Edit Export
        private void EditExportToolStripMenuItem_Click(object sender, EventArgs e)
        {
            EditGroup.Visible = true;

            FolderBrowserDialog folderBrowserDialog1 = new FolderBrowserDialog
            {
                Description = "Select Export folder",
                ShowNewFolderButton = true
            };

            if (folderBrowserDialog1.ShowDialog() == DialogResult.OK)
            {
                try
                {
                    string selectedFolderPath = folderBrowserDialog1.SelectedPath;

                    // Resize the form
                    this.Size = new Size(927, 633);

                    // Clear existing nodes from ExportTreeView
                    ExportTreeView.Nodes.Clear();

                    // Populate the ExportTreeView with the folder structure
                    PopulateExportTreeView(selectedFolderPath, ExportTreeView.Nodes);
                }
                catch (Exception ex)
                {
                    MessageBox.Show("Error: Could not open Export folder. Original error: " + ex.Message);
                }
            }
        }

        private void PopulateExportTreeView(string directory, TreeNodeCollection parentNode)
        {
            // Get all directories within the specified directory
            string[] subDirectories = Directory.GetDirectories(directory);

            // Iterate through each subdirectory
            foreach (string subDirectory in subDirectories)
            {
                // Add a node for the subdirectory to the parent node
                TreeNode node = parentNode.Add(Path.GetFileName(subDirectory));

                // Recursively populate the subdirectory
                PopulateExportTreeView(subDirectory, node.Nodes);
            }

            // Get all files within the specified directory
            string[] files = Directory.GetFiles(directory);

            // Iterate through each file
            foreach (string file in files)
            {
                // Add a node for the file to the parent node
                parentNode.Add(Path.GetFileName(file));
            }
        }
        #endregion

        #region RePak
        private void rePakToolStripMenuItem_Click(object sender, EventArgs e)
        {
            EditGroup.Visible = false;

            this.Size = new Size(280, 100);

            FolderBrowserDialog folderBrowserDialog1 = new FolderBrowserDialog
            {
                Description = "Select folder to RePak",
                ShowNewFolderButton = true
            };

            if (folderBrowserDialog1.ShowDialog() == DialogResult.OK)
            {
                try
                {
                    // Jev - Placeholder
                    string selectedFolderPath = folderBrowserDialog1.SelectedPath;
                }
                catch (Exception ex)
                {
                    MessageBox.Show("Error: Could not select folder for RePak. Original error: " + ex.Message);
                }
            }
        }

        #endregion
    }
}
