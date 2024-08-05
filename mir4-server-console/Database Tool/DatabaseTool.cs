using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Data;
using System.Drawing;
using System.IO;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using System.Windows.Forms;

namespace Server_Console.Database_Tool
{
    public partial class DatabaseTool : Form
    {
        public DatabaseTool()
        {
            InitializeComponent();
        }
        private void DatabaseTool_Load(object sender, EventArgs e)
        {
            string dataFolderPath = Path.Combine(Application.StartupPath, "Data");

            if (!Directory.Exists(dataFolderPath))
            {
                Directory.CreateDirectory(dataFolderPath);
            }
            else
            {
            }
        }
    }
}
