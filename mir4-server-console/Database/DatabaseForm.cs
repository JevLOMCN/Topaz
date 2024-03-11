using System;
using System.Collections.Generic;
using System.IO;
using System.Text;
using System.Windows.Forms;

namespace Server_Console.Database
{
    public partial class DatabaseForm : Form
    {
        private List<string> schemaNames = new List<string>();

        public DatabaseForm()
        {
            InitializeComponent();
            this.Load += DatabaseForm_Load;
        }

        private void DatabaseForm_Load(object sender, EventArgs e)
        {
            LoadSchemaNames();
        }

        #region DB Load
        private void LoadSchemaNames()
        {
            string baseDirectory = AppDomain.CurrentDomain.BaseDirectory;
            string sqlFilePath = Path.Combine(baseDirectory, "DB", "DB.sql");

            if (File.Exists(sqlFilePath))
            {
                Console.WriteLine("SQL file found: " + sqlFilePath);

                using (StreamReader reader = new StreamReader(sqlFilePath, Encoding.UTF8))
                {
                    string line;
                    while ((line = reader.ReadLine()) != null)
                    {
                        string schemaName = ExtractSchemaName(line);
                        if (schemaName != null && !schemaNames.Contains(schemaName))
                        {
                            schemaNames.Add(schemaName);
                        }
                    }
                }

                // Load schema names into DBTree
                foreach (string schemaName in schemaNames)
                {
                    TreeNode node = new TreeNode(schemaName);
                    DBTree.Nodes.Add(node);
                }
            }
            else
            {
                Console.WriteLine("SQL file not found: " + sqlFilePath);
                MessageBox.Show("SQL file not found: " + sqlFilePath, "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private string ExtractSchemaName(string line)
        {
            string[] parts = line.Split(new char[] { '.', ' ' }, StringSplitOptions.RemoveEmptyEntries);
            if (parts.Length > 2 && parts[0].Equals("CREATE", StringComparison.OrdinalIgnoreCase) && parts[1].Equals("TABLE", StringComparison.OrdinalIgnoreCase))
            {
                return parts[2];
            }
            return null;
        }
        #endregion
    }
}