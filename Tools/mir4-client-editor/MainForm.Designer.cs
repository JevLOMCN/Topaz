namespace Mir_4_Client_Editor
{
    partial class MainForm
    {
        /// <summary>
        ///  Required designer variable.
        /// </summary>
        private System.ComponentModel.IContainer components = null;

        /// <summary>
        ///  Clean up any resources being used.
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
        ///  Required method for Designer support - do not modify
        ///  the contents of this method with the code editor.
        /// </summary>
        private void InitializeComponent()
        {
            System.ComponentModel.ComponentResourceManager resources = new System.ComponentModel.ComponentResourceManager(typeof(MainForm));
            MainMenu = new MenuStrip();
            ExportToolStripMenuItem = new ToolStripMenuItem();
            EditExportToolStripMenuItem = new ToolStripMenuItem();
            rePakToolStripMenuItem = new ToolStripMenuItem();
            EditGroup = new GroupBox();
            ExportGridView = new DataGridView();
            ExportTreeView = new TreeView();
            MainMenu.SuspendLayout();
            EditGroup.SuspendLayout();
            ((System.ComponentModel.ISupportInitialize)ExportGridView).BeginInit();
            SuspendLayout();
            // 
            // MainMenu
            // 
            MainMenu.Dock = DockStyle.None;
            MainMenu.Items.AddRange(new ToolStripItem[] { ExportToolStripMenuItem, EditExportToolStripMenuItem, rePakToolStripMenuItem });
            MainMenu.Location = new Point(0, 0);
            MainMenu.Name = "MainMenu";
            MainMenu.Size = new Size(188, 24);
            MainMenu.Stretch = false;
            MainMenu.TabIndex = 0;
            MainMenu.Text = "Main Menu";
            // 
            // ExportToolStripMenuItem
            // 
            ExportToolStripMenuItem.Name = "ExportToolStripMenuItem";
            ExportToolStripMenuItem.Size = new Size(53, 20);
            ExportToolStripMenuItem.Text = "Export";
            ExportToolStripMenuItem.Click += ExportToolStripMenuItem_Click;
            // 
            // EditExportToolStripMenuItem
            // 
            EditExportToolStripMenuItem.Name = "EditExportToolStripMenuItem";
            EditExportToolStripMenuItem.Size = new Size(76, 20);
            EditExportToolStripMenuItem.Text = "Edit Export";
            EditExportToolStripMenuItem.Click += EditExportToolStripMenuItem_Click;
            // 
            // rePakToolStripMenuItem
            // 
            rePakToolStripMenuItem.Name = "rePakToolStripMenuItem";
            rePakToolStripMenuItem.Size = new Size(51, 20);
            rePakToolStripMenuItem.Text = "RePak";
            rePakToolStripMenuItem.Click += rePakToolStripMenuItem_Click;
            // 
            // EditGroup
            // 
            EditGroup.Controls.Add(ExportGridView);
            EditGroup.Controls.Add(ExportTreeView);
            EditGroup.Dock = DockStyle.Bottom;
            EditGroup.Location = new Point(0, -506);
            EditGroup.Name = "EditGroup";
            EditGroup.Size = new Size(265, 567);
            EditGroup.TabIndex = 1;
            EditGroup.TabStop = false;
            EditGroup.Visible = false;
            // 
            // ExportGridView
            // 
            ExportGridView.BackgroundColor = SystemColors.Window;
            ExportGridView.ColumnHeadersHeightSizeMode = DataGridViewColumnHeadersHeightSizeMode.AutoSize;
            ExportGridView.Dock = DockStyle.Right;
            ExportGridView.Location = new Point(-445, 19);
            ExportGridView.Name = "ExportGridView";
            ExportGridView.Size = new Size(707, 545);
            ExportGridView.TabIndex = 1;
            // 
            // ExportTreeView
            // 
            ExportTreeView.Dock = DockStyle.Left;
            ExportTreeView.FullRowSelect = true;
            ExportTreeView.Location = new Point(3, 19);
            ExportTreeView.Name = "ExportTreeView";
            ExportTreeView.Size = new Size(192, 545);
            ExportTreeView.TabIndex = 0;
            // 
            // MainForm
            // 
            AutoScaleDimensions = new SizeF(7F, 15F);
            AutoScaleMode = AutoScaleMode.Font;
            ClientSize = new Size(265, 61);
            Controls.Add(EditGroup);
            Controls.Add(MainMenu);
            Icon = (Icon)resources.GetObject("$this.Icon");
            MainMenuStrip = MainMenu;
            Name = "MainForm";
            Text = "Mir 4 Client Editor";
            Load += MainForm_Load;
            MainMenu.ResumeLayout(false);
            MainMenu.PerformLayout();
            EditGroup.ResumeLayout(false);
            ((System.ComponentModel.ISupportInitialize)ExportGridView).EndInit();
            ResumeLayout(false);
            PerformLayout();
        }

        #endregion

        private MenuStrip MainMenu;
        private ToolStripMenuItem ExportToolStripMenuItem;
        private ToolStripMenuItem EditExportToolStripMenuItem;
        private ToolStripMenuItem rePakToolStripMenuItem;
        private GroupBox EditGroup;
        private TreeView ExportTreeView;
        private DataGridView ExportGridView;
    }
}
