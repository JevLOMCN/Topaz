namespace Server_Console
{
    partial class JSONForm
    {
        /// <summary>
        /// Required designer variable.
        /// </summary>
        private System.ComponentModel.IContainer components = null;

        /// <summary>
        /// Clean up any resources being used.
        /// </summary>
        /// <param name="disposing">true if managed resources should be disposed; otherwise, false.</param>
        protected override void Dispose(bool disposing)
        {
            if (disposing && (components != null))
            {
                components.Dispose();
            }
            base.Dispose(disposing);
        }

        #region Windows Form Designer generated code

        /// <summary>
        /// Required method for Designer support - do not modify
        /// the contents of this method with the code editor.
        /// </summary>
        private void InitializeComponent()
        {
            System.ComponentModel.ComponentResourceManager resources = new System.ComponentModel.ComponentResourceManager(typeof(JSONForm));
            FileList = new TreeView();
            JsonDataGrid = new DataGridView();
            JSONMenu = new MenuStrip();
            Tools = new ToolStripMenuItem();
            ExportJSONsToolStripMenuItem = new ToolStripMenuItem();
            ((System.ComponentModel.ISupportInitialize)JsonDataGrid).BeginInit();
            JSONMenu.SuspendLayout();
            SuspendLayout();
            // 
            // FileList
            // 
            FileList.Anchor = AnchorStyles.Left;
            FileList.BackColor = SystemColors.Window;
            FileList.BorderStyle = BorderStyle.None;
            FileList.ForeColor = SystemColors.WindowText;
            FileList.FullRowSelect = true;
            FileList.LineColor = Color.White;
            FileList.Location = new Point(0, 26);
            FileList.Name = "FileList";
            FileList.Size = new Size(188, 424);
            FileList.TabIndex = 0;
            FileList.AfterSelect += FileList_AfterSelect;
            FileList.MouseClick += FileList_MouseClick;
            // 
            // JsonDataGrid
            // 
            JsonDataGrid.Anchor = AnchorStyles.Right;
            JsonDataGrid.BackgroundColor = SystemColors.Window;
            JsonDataGrid.ColumnHeadersHeightSizeMode = DataGridViewColumnHeadersHeightSizeMode.AutoSize;
            JsonDataGrid.GridColor = Color.Black;
            JsonDataGrid.Location = new Point(188, 26);
            JsonDataGrid.Name = "JsonDataGrid";
            JsonDataGrid.Size = new Size(804, 424);
            JsonDataGrid.TabIndex = 1;
            // 
            // JSONMenu
            // 
            JSONMenu.BackColor = SystemColors.Window;
            JSONMenu.Items.AddRange(new ToolStripItem[] { Tools });
            JSONMenu.Location = new Point(0, 0);
            JSONMenu.Name = "JSONMenu";
            JSONMenu.Size = new Size(992, 24);
            JSONMenu.TabIndex = 2;
            JSONMenu.Text = "menuStrip1";
            // 
            // Tools
            // 
            Tools.DropDownItems.AddRange(new ToolStripItem[] { ExportJSONsToolStripMenuItem });
            Tools.Name = "Tools";
            Tools.Size = new Size(46, 20);
            Tools.Text = "Tools";
            // 
            // ExportJSONsToolStripMenuItem
            // 
            ExportJSONsToolStripMenuItem.Name = "ExportJSONsToolStripMenuItem";
            ExportJSONsToolStripMenuItem.Size = new Size(180, 22);
            ExportJSONsToolStripMenuItem.Text = "Export All";
            ExportJSONsToolStripMenuItem.Click += ExportJSONsToolStripMenuItem_Click;
            // 
            // JSONForm
            // 
            AutoScaleMode = AutoScaleMode.None;
            BackColor = SystemColors.Window;
            ClientSize = new Size(992, 450);
            Controls.Add(JsonDataGrid);
            Controls.Add(FileList);
            Controls.Add(JSONMenu);
            ForeColor = SystemColors.WindowText;
            Icon = (Icon)resources.GetObject("$this.Icon");
            MainMenuStrip = JSONMenu;
            Name = "JSONForm";
            StartPosition = FormStartPosition.CenterScreen;
            Text = "JSONForm";
            ((System.ComponentModel.ISupportInitialize)JsonDataGrid).EndInit();
            JSONMenu.ResumeLayout(false);
            JSONMenu.PerformLayout();
            ResumeLayout(false);
            PerformLayout();
        }

        #endregion

        private TreeView FileList;
        private DataGridView JsonDataGrid;
        private MenuStrip JSONMenu;
        private ToolStripMenuItem Tools;
        private ToolStripMenuItem ExportJSONsToolStripMenuItem;
    }
}