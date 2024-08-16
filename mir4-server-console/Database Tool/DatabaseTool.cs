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
            string dataFolderPath = Path.Combine(Application.StartupPath, "Assets");

            if (!Directory.Exists(dataFolderPath))
            {
                Directory.CreateDirectory(dataFolderPath);
            }
            else
            {
            }

            LoadMaps();
        }

        private void LoadMaps()
        {
            string imagePath = "Assets/Maps/WorldMap.png";
            if (System.IO.File.Exists(imagePath))
            {
                Bitmap map = new Bitmap(imagePath);
                WorldMapBox.Image = map;
            }
            else
            {
                MessageBox.Show("Image file not found!");
            }
        }
    }
}
