using Server_Console.Properties;
using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Windows.Forms;

namespace Server_Console.Database_Tool
{
    public class CommandPage : Form
    {
        private static DataGridView CommandsGrid;

        public CommandPage()
        {
            System.ComponentModel.ComponentResourceManager resources = new System.ComponentModel.ComponentResourceManager(typeof(DatabaseTool));
            this.BackColor = Color.White;
            AutoScaleDimensions = new SizeF(7F, 15F);
            AutoScaleMode = AutoScaleMode.Font;
            ClientSize = new Size(1400, 800);
            Icon = (Icon)resources.GetObject("$this.Icon");
            Name = "DatabaseTool";
            Text = "DatabaseTool - Commands";
            StartPosition = FormStartPosition.CenterScreen;

            InitializeComponent();
            Initialize();
        }

        private void InitializeComponent()
        {
            CommandsGrid = new DataGridView
            {
                Dock = DockStyle.Fill,
                AutoGenerateColumns = false
            };
            this.Controls.Add(CommandsGrid);

            var columns = new[]
            {
                new ColumnInfo { Header = "Type", Property = "Type", Width = 200},
                new ColumnInfo { Header = "Command", Property = "CommandName", Width = 200},
                new ColumnInfo { Header = "Parameters", Property = "Parameters" , Width = 300},
                new ColumnInfo { Header = "Function", Property = "Function", Width = 300},
                new ColumnInfo { Header = "Notes", Property = "Notes", Width = 500}
            };

            foreach (var col in columns)
            {
                CommandsGrid.Columns.Add(new DataGridViewTextBoxColumn
                {
                    HeaderText = col.Header,
                    Name = col.Property,
                    DataPropertyName = col.Property,
                    Width = col.Width
                });
            }
        }

        public static void Initialize()
        {
            string csvFilePath = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "Assets", "CSV", "Commands.csv");
            var commands = FileManager.LoadCsv<Command>(csvFilePath);

            if (commands.Any())
            {
                CommandsGrid.DataSource = new BindingSource { DataSource = commands };
            }
        }

        public class Command
        {
            public string? Type { get; set; }
            public string? CommandName { get; set; }
            public string? Parameters { get; set; }
            public string? Function { get; set; }
            public string? Notes { get; set; }
        }

        public class ColumnInfo
        {
            public string? Header { get; set; }
            public string? Property { get; set; }
            public int Width { get; set; }
        }
    }
}
