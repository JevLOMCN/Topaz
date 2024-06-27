namespace Mir_4_API_Tool
{
    public partial class Main : Form
    {
        public Main()
        {
            InitializeComponent();
        }

        private void openAPIDirToolStripMenuItem_Click(object sender, EventArgs e)
        {
            using (FolderBrowserDialog folderDialog = new FolderBrowserDialog())
            {
                folderDialog.Description = "Select your API Folder";
                folderDialog.SelectedPath = Environment.CurrentDirectory;

                if (folderDialog.ShowDialog() == DialogResult.OK)
                {
                    string selectedFolderPath = folderDialog.SelectedPath;
                }
            }
        }
    }
}
