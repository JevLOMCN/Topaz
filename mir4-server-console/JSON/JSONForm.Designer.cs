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
            ((System.ComponentModel.ISupportInitialize)JsonDataGrid).BeginInit();
            SuspendLayout();
            // 
            // FileList
            // 
            FileList.BackColor = SystemColors.Window;
            FileList.BorderStyle = BorderStyle.None;
            FileList.Dock = DockStyle.Left;
            FileList.ForeColor = SystemColors.WindowText;
            FileList.FullRowSelect = true;
            FileList.LineColor = Color.White;
            FileList.Location = new Point(0, 0);
            FileList.Name = "FileList";
            FileList.Size = new Size(188, 450);
            FileList.TabIndex = 0;
            FileList.AfterSelect += FileList_AfterSelect;
            FileList.MouseClick += FileList_MouseClick;
            // 
            // JsonDataGrid
            // 
            JsonDataGrid.BackgroundColor = SystemColors.Window;
            JsonDataGrid.ColumnHeadersHeightSizeMode = DataGridViewColumnHeadersHeightSizeMode.AutoSize;
            JsonDataGrid.Dock = DockStyle.Fill;
            JsonDataGrid.GridColor = Color.Black;
            JsonDataGrid.Location = new Point(188, 0);
            JsonDataGrid.Name = "JsonDataGrid";
            JsonDataGrid.Size = new Size(804, 450);
            JsonDataGrid.TabIndex = 1;
            // 
            // JSONForm
            // 
            AutoScaleMode = AutoScaleMode.None;
            BackColor = SystemColors.Window;
            ClientSize = new Size(992, 450);
            Controls.Add(JsonDataGrid);
            Controls.Add(FileList);
            ForeColor = SystemColors.WindowText;
            Icon = (Icon)resources.GetObject("$this.Icon");
            Name = "JSONForm";
            StartPosition = FormStartPosition.CenterScreen;
            Text = "JSONForm";
            ((System.ComponentModel.ISupportInitialize)JsonDataGrid).EndInit();
            ResumeLayout(false);
        }

        #endregion

        private TreeView FileList;
        private DataGridView JsonDataGrid;
    }
}