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
using System.Formats.Asn1;
using System.Globalization;
using CsvHelper;
using CsvHelper.Configuration.Attributes;
using CsvHelper.Configuration;

namespace Server_Console.Database_Tool
{
    public partial class DatabaseTool : Form
    {
        public DatabaseTool()
        {
            InitializeComponent();
            ConfigureCommandsGrid();
        }
        public class Command
        {
            public string Type { get; set; }
            public string CommandName { get; set; }
            public string Parameters { get; set; }
            public string Function { get; set; }
            public string Notes { get; set; }
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
            LoadCommands();
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
        private void LoadCommands()
        {
            var csvFilePath = Path.Combine(Application.StartupPath, "Assets", "CSV", "Commands.csv");

            if (File.Exists(csvFilePath))
            {
                using (var reader = new StreamReader(csvFilePath))
                using (var csv = new CsvReader(reader, new CsvConfiguration(CultureInfo.InvariantCulture)
                {
                    HeaderValidated = null, // Ignore header validation
                    MissingFieldFound = null // Ignore missing fields
                }))
                {
                    // Read the CSV file into a list of Command objects
                    var commands = csv.GetRecords<Command>().ToList();

                    // Bind the list to the CommandsGrid
                    CommandsGrid.DataSource = new BindingSource { DataSource = commands };
                }
            }
            else
            {
                MessageBox.Show("CSV file not found.");
            }
        }
        private void ConfigureCommandsGrid()
        {
            // Define columns in the grid
            CommandsGrid.AutoGenerateColumns = false; // Disable auto generation of columns

            CommandsGrid.Columns.Add(new DataGridViewTextBoxColumn
            {
                HeaderText = "Type",
                Name = "Type",
                DataPropertyName = "Type" // Ensure binding matches property name
            });
            CommandsGrid.Columns.Add(new DataGridViewTextBoxColumn
            {
                HeaderText = "Command",
                Name = "CommandName",
                DataPropertyName = "CommandName" // Ensure binding matches property name
            });
            CommandsGrid.Columns.Add(new DataGridViewTextBoxColumn
            {
                HeaderText = "Parameters",
                Name = "Parameters",
                DataPropertyName = "Parameters" // Ensure binding matches property name
            });
            CommandsGrid.Columns.Add(new DataGridViewTextBoxColumn
            {
                HeaderText = "Function",
                Name = "Function",
                DataPropertyName = "Function" // Ensure binding matches property name
            });
            CommandsGrid.Columns.Add(new DataGridViewTextBoxColumn
            {
                HeaderText = "Notes",
                Name = "Notes",
                DataPropertyName = "Notes", // Ensure binding matches property name
                Width = 400 // Set width here
            });
        }
    }
}