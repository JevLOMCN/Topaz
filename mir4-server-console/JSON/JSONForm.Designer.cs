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
            FileList = new TreeView();
            JSONTextBox = new RichTextBox();
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
            // JSONTextBox
            // 
            JSONTextBox.BackColor = SystemColors.Window;
            JSONTextBox.BorderStyle = BorderStyle.None;
            JSONTextBox.Dock = DockStyle.Right;
            JSONTextBox.ForeColor = SystemColors.WindowText;
            JSONTextBox.Location = new Point(310, 0);
            JSONTextBox.Name = "JSONTextBox";
            JSONTextBox.Size = new Size(490, 450);
            JSONTextBox.TabIndex = 3;
            JSONTextBox.Text = "";
            // 
            // JSONForm
            // 
            AutoScaleMode = AutoScaleMode.None;
            BackColor = SystemColors.Window;
            ClientSize = new Size(800, 450);
            Controls.Add(JSONTextBox);
            Controls.Add(FileList);
            ForeColor = SystemColors.WindowText;
            FormBorderStyle = FormBorderStyle.None;
            Name = "JSONForm";
            ShowInTaskbar = false;
            StartPosition = FormStartPosition.CenterScreen;
            Text = "JSONForm";
            ResumeLayout(false);
        }

        #endregion

        private TreeView FileList;
        private RichTextBox JSONTextBox;
    }
}