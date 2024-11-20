namespace Server_Console.JSON
{
    partial class RespawnForm
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
            System.ComponentModel.ComponentResourceManager resources = new System.ComponentModel.ComponentResourceManager(typeof(RespawnForm));
            RespawnData = new ListView();
            Jsons = new ColumnHeader();
            RespawnContents = new RichTextBox();
            RespawnList = new ComboBox();
            label1 = new Label();
            DeleteRespawnButton = new Button();
            groupBox1 = new GroupBox();
            SaveButton = new Button();
            label8 = new Label();
            PosID = new TextBox();
            label6 = new Label();
            label7 = new Label();
            label4 = new Label();
            label5 = new Label();
            label3 = new Label();
            label2 = new Label();
            LocationZ = new TextBox();
            LocationY = new TextBox();
            LocationX = new TextBox();
            SpawnRange = new TextBox();
            SpawnCount = new TextBox();
            MonsterID = new TextBox();
            groupBox2 = new GroupBox();
            MonsterCountLabel = new Label();
            label11 = new Label();
            FilterMonsterID = new TextBox();
            OpenJSONButton = new Button();
            CreateNewGenFile = new Button();
            FileCountLabel = new Label();
            RefreshList = new Button();
            label9 = new Label();
            FilterGenName = new TextBox();
            ExportGenFile = new Button();
            DeleteGenFile = new Button();
            label10 = new Label();
            TutorialLink = new LinkLabel();
            groupBox1.SuspendLayout();
            groupBox2.SuspendLayout();
            SuspendLayout();
            // 
            // RespawnData
            // 
            RespawnData.BorderStyle = BorderStyle.None;
            RespawnData.Columns.AddRange(new ColumnHeader[] { Jsons });
            RespawnData.Dock = DockStyle.Left;
            RespawnData.FullRowSelect = true;
            RespawnData.GridLines = true;
            RespawnData.HeaderStyle = ColumnHeaderStyle.None;
            RespawnData.Location = new Point(0, 0);
            RespawnData.Name = "RespawnData";
            RespawnData.Size = new Size(215, 678);
            RespawnData.TabIndex = 0;
            RespawnData.UseCompatibleStateImageBehavior = false;
            RespawnData.View = View.Details;
            RespawnData.SelectedIndexChanged += RespawnData_SelectedIndexChanged;
            // 
            // Jsons
            // 
            Jsons.Text = "";
            Jsons.Width = 215;
            // 
            // RespawnContents
            // 
            RespawnContents.BorderStyle = BorderStyle.FixedSingle;
            RespawnContents.Dock = DockStyle.Right;
            RespawnContents.Location = new Point(482, 0);
            RespawnContents.Name = "RespawnContents";
            RespawnContents.Size = new Size(619, 678);
            RespawnContents.TabIndex = 1;
            RespawnContents.Text = "";
            // 
            // RespawnList
            // 
            RespawnList.FormattingEnabled = true;
            RespawnList.Location = new Point(83, 22);
            RespawnList.Name = "RespawnList";
            RespawnList.Size = new Size(121, 23);
            RespawnList.TabIndex = 2;
            RespawnList.SelectedIndexChanged += RespawnList_SelectedIndexChanged;
            // 
            // label1
            // 
            label1.AutoSize = true;
            label1.Location = new Point(9, 25);
            label1.Name = "label1";
            label1.Size = new Size(57, 15);
            label1.TabIndex = 3;
            label1.Text = "Respawn:";
            // 
            // DeleteRespawnButton
            // 
            DeleteRespawnButton.Location = new Point(83, 293);
            DeleteRespawnButton.Name = "DeleteRespawnButton";
            DeleteRespawnButton.Size = new Size(93, 23);
            DeleteRespawnButton.TabIndex = 5;
            DeleteRespawnButton.Text = "Del Respawn";
            DeleteRespawnButton.UseVisualStyleBackColor = true;
            DeleteRespawnButton.Click += DeleteRespawnButton_Click;
            // 
            // groupBox1
            // 
            groupBox1.Controls.Add(SaveButton);
            groupBox1.Controls.Add(label8);
            groupBox1.Controls.Add(PosID);
            groupBox1.Controls.Add(label6);
            groupBox1.Controls.Add(label7);
            groupBox1.Controls.Add(label4);
            groupBox1.Controls.Add(label5);
            groupBox1.Controls.Add(label3);
            groupBox1.Controls.Add(label2);
            groupBox1.Controls.Add(LocationZ);
            groupBox1.Controls.Add(LocationY);
            groupBox1.Controls.Add(LocationX);
            groupBox1.Controls.Add(SpawnRange);
            groupBox1.Controls.Add(SpawnCount);
            groupBox1.Controls.Add(MonsterID);
            groupBox1.Controls.Add(RespawnList);
            groupBox1.Controls.Add(DeleteRespawnButton);
            groupBox1.Controls.Add(label1);
            groupBox1.Location = new Point(221, 0);
            groupBox1.Name = "groupBox1";
            groupBox1.Size = new Size(255, 327);
            groupBox1.TabIndex = 6;
            groupBox1.TabStop = false;
            groupBox1.Text = "Respawns";
            // 
            // SaveButton
            // 
            SaveButton.Location = new Point(83, 264);
            SaveButton.Name = "SaveButton";
            SaveButton.Size = new Size(93, 23);
            SaveButton.TabIndex = 20;
            SaveButton.Text = "Save";
            SaveButton.UseVisualStyleBackColor = true;
            SaveButton.Click += SaveButton_Click;
            // 
            // label8
            // 
            label8.AutoSize = true;
            label8.Location = new Point(9, 64);
            label8.Name = "label8";
            label8.Size = new Size(43, 15);
            label8.TabIndex = 19;
            label8.Text = "Pos ID:";
            // 
            // PosID
            // 
            PosID.Location = new Point(83, 61);
            PosID.Name = "PosID";
            PosID.ReadOnly = true;
            PosID.Size = new Size(46, 23);
            PosID.TabIndex = 18;
            // 
            // label6
            // 
            label6.AutoSize = true;
            label6.Location = new Point(9, 238);
            label6.Name = "label6";
            label6.Size = new Size(17, 15);
            label6.TabIndex = 17;
            label6.Text = "Z:";
            // 
            // label7
            // 
            label7.AutoSize = true;
            label7.Location = new Point(9, 209);
            label7.Name = "label7";
            label7.Size = new Size(17, 15);
            label7.TabIndex = 16;
            label7.Text = "Y:";
            // 
            // label4
            // 
            label4.AutoSize = true;
            label4.Location = new Point(9, 180);
            label4.Name = "label4";
            label4.Size = new Size(17, 15);
            label4.TabIndex = 15;
            label4.Text = "X:";
            // 
            // label5
            // 
            label5.AutoSize = true;
            label5.Location = new Point(9, 151);
            label5.Name = "label5";
            label5.Size = new Size(43, 15);
            label5.TabIndex = 14;
            label5.Text = "Range:";
            // 
            // label3
            // 
            label3.AutoSize = true;
            label3.Location = new Point(9, 122);
            label3.Name = "label3";
            label3.Size = new Size(43, 15);
            label3.TabIndex = 13;
            label3.Text = "Count:";
            // 
            // label2
            // 
            label2.AutoSize = true;
            label2.Location = new Point(9, 93);
            label2.Name = "label2";
            label2.Size = new Size(68, 15);
            label2.TabIndex = 12;
            label2.Text = "Monster ID:";
            // 
            // LocationZ
            // 
            LocationZ.Location = new Point(83, 235);
            LocationZ.Name = "LocationZ";
            LocationZ.Size = new Size(100, 23);
            LocationZ.TabIndex = 11;
            LocationZ.TextChanged += LocationZ_TextChanged;
            // 
            // LocationY
            // 
            LocationY.Location = new Point(83, 206);
            LocationY.Name = "LocationY";
            LocationY.Size = new Size(100, 23);
            LocationY.TabIndex = 10;
            LocationY.TextChanged += LocationY_TextChanged;
            // 
            // LocationX
            // 
            LocationX.Location = new Point(83, 177);
            LocationX.Name = "LocationX";
            LocationX.Size = new Size(100, 23);
            LocationX.TabIndex = 9;
            LocationX.TextChanged += LocationX_TextChanged;
            // 
            // SpawnRange
            // 
            SpawnRange.Location = new Point(83, 148);
            SpawnRange.Name = "SpawnRange";
            SpawnRange.Size = new Size(100, 23);
            SpawnRange.TabIndex = 8;
            SpawnRange.TextChanged += SpawnRange_TextChanged;
            // 
            // SpawnCount
            // 
            SpawnCount.Location = new Point(83, 119);
            SpawnCount.Name = "SpawnCount";
            SpawnCount.Size = new Size(100, 23);
            SpawnCount.TabIndex = 7;
            SpawnCount.TextChanged += SpawnCount_TextChanged;
            // 
            // MonsterID
            // 
            MonsterID.Location = new Point(83, 90);
            MonsterID.Name = "MonsterID";
            MonsterID.Size = new Size(100, 23);
            MonsterID.TabIndex = 6;
            MonsterID.TextChanged += MonsterID_TextChanged;
            // 
            // groupBox2
            // 
            groupBox2.Controls.Add(MonsterCountLabel);
            groupBox2.Controls.Add(label11);
            groupBox2.Controls.Add(FilterMonsterID);
            groupBox2.Controls.Add(OpenJSONButton);
            groupBox2.Controls.Add(CreateNewGenFile);
            groupBox2.Controls.Add(FileCountLabel);
            groupBox2.Controls.Add(RefreshList);
            groupBox2.Controls.Add(label9);
            groupBox2.Controls.Add(FilterGenName);
            groupBox2.Controls.Add(ExportGenFile);
            groupBox2.Controls.Add(DeleteGenFile);
            groupBox2.Location = new Point(221, 333);
            groupBox2.Name = "groupBox2";
            groupBox2.Size = new Size(255, 282);
            groupBox2.TabIndex = 7;
            groupBox2.TabStop = false;
            groupBox2.Text = "Gen File";
            // 
            // MonsterCountLabel
            // 
            MonsterCountLabel.AutoSize = true;
            MonsterCountLabel.Location = new Point(9, 214);
            MonsterCountLabel.Name = "MonsterCountLabel";
            MonsterCountLabel.Size = new Size(98, 15);
            MonsterCountLabel.TabIndex = 10;
            MonsterCountLabel.Text = "Monsters Count: ";
            // 
            // label11
            // 
            label11.AutoSize = true;
            label11.Location = new Point(9, 117);
            label11.Name = "label11";
            label11.Size = new Size(113, 15);
            label11.TabIndex = 9;
            label11.Text = "Filter by Monster ID:";
            // 
            // FilterMonsterID
            // 
            FilterMonsterID.Location = new Point(128, 114);
            FilterMonsterID.Name = "FilterMonsterID";
            FilterMonsterID.Size = new Size(121, 23);
            FilterMonsterID.TabIndex = 8;
            // 
            // OpenJSONButton
            // 
            OpenJSONButton.Location = new Point(162, 51);
            OpenJSONButton.Name = "OpenJSONButton";
            OpenJSONButton.Size = new Size(87, 23);
            OpenJSONButton.TabIndex = 7;
            OpenJSONButton.Text = "Open JSON";
            OpenJSONButton.UseVisualStyleBackColor = true;
            OpenJSONButton.Click += OpenJSONButton_Click;
            // 
            // CreateNewGenFile
            // 
            CreateNewGenFile.Location = new Point(162, 22);
            CreateNewGenFile.Name = "CreateNewGenFile";
            CreateNewGenFile.Size = new Size(87, 23);
            CreateNewGenFile.TabIndex = 6;
            CreateNewGenFile.Text = "Create New";
            CreateNewGenFile.UseVisualStyleBackColor = true;
            CreateNewGenFile.Click += CreateNewGenFile_Click;
            // 
            // FileCountLabel
            // 
            FileCountLabel.AutoSize = true;
            FileCountLabel.Location = new Point(9, 193);
            FileCountLabel.Name = "FileCountLabel";
            FileCountLabel.Size = new Size(91, 15);
            FileCountLabel.TabIndex = 5;
            FileCountLabel.Text = "Gen File Count: ";
            // 
            // RefreshList
            // 
            RefreshList.Location = new Point(83, 156);
            RefreshList.Name = "RefreshList";
            RefreshList.Size = new Size(75, 23);
            RefreshList.TabIndex = 4;
            RefreshList.Text = "Refresh";
            RefreshList.UseVisualStyleBackColor = true;
            RefreshList.Click += RefreshList_Click;
            // 
            // label9
            // 
            label9.AutoSize = true;
            label9.Location = new Point(9, 88);
            label9.Name = "label9";
            label9.Size = new Size(103, 15);
            label9.TabIndex = 3;
            label9.Text = "Filter by Filename:";
            // 
            // FilterGenName
            // 
            FilterGenName.Location = new Point(118, 85);
            FilterGenName.Name = "FilterGenName";
            FilterGenName.Size = new Size(131, 23);
            FilterGenName.TabIndex = 2;
            // 
            // ExportGenFile
            // 
            ExportGenFile.Location = new Point(9, 51);
            ExportGenFile.Name = "ExportGenFile";
            ExportGenFile.Size = new Size(147, 23);
            ExportGenFile.TabIndex = 1;
            ExportGenFile.Text = "Export Selected Gen File";
            ExportGenFile.UseVisualStyleBackColor = true;
            ExportGenFile.Click += ExportGenFile_Click;
            // 
            // DeleteGenFile
            // 
            DeleteGenFile.Location = new Point(9, 22);
            DeleteGenFile.Name = "DeleteGenFile";
            DeleteGenFile.Size = new Size(147, 23);
            DeleteGenFile.TabIndex = 0;
            DeleteGenFile.Text = "Delete Selected Gen File";
            DeleteGenFile.UseVisualStyleBackColor = true;
            DeleteGenFile.Click += DeleteGenFile_Click;
            // 
            // label10
            // 
            label10.AutoSize = true;
            label10.Location = new Point(221, 618);
            label10.Name = "label10";
            label10.Size = new Size(252, 30);
            label10.TabIndex = 8;
            label10.Text = "Please note: Servers MUST be restarted for any \r\nupdated repsawn data to take effect !";
            // 
            // TutorialLink
            // 
            TutorialLink.AutoSize = true;
            TutorialLink.Location = new Point(221, 657);
            TutorialLink.Name = "TutorialLink";
            TutorialLink.Size = new Size(97, 15);
            TutorialLink.TabIndex = 9;
            TutorialLink.TabStop = true;
            TutorialLink.Text = "Respawn Tutorial";
            TutorialLink.LinkClicked += TutorialLink_LinkClicked;
            // 
            // RespawnForm
            // 
            AutoScaleDimensions = new SizeF(7F, 15F);
            AutoScaleMode = AutoScaleMode.Font;
            ClientSize = new Size(1101, 678);
            Controls.Add(TutorialLink);
            Controls.Add(label10);
            Controls.Add(groupBox2);
            Controls.Add(groupBox1);
            Controls.Add(RespawnContents);
            Controls.Add(RespawnData);
            Icon = (Icon)resources.GetObject("$this.Icon");
            Name = "RespawnForm";
            Text = "Respawns";
            groupBox1.ResumeLayout(false);
            groupBox1.PerformLayout();
            groupBox2.ResumeLayout(false);
            groupBox2.PerformLayout();
            ResumeLayout(false);
            PerformLayout();
        }

        #endregion

        private ListView RespawnData;
        private ColumnHeader Jsons;
        private RichTextBox RespawnContents;
        private ComboBox RespawnList;
        private Label label1;
        private Button DeleteRespawnButton;
        private GroupBox groupBox1;
        private TextBox SpawnRange;
        private TextBox SpawnCount;
        private TextBox MonsterID;
        private Label label8;
        private TextBox PosID;
        private Label label6;
        private Label label7;
        private Label label4;
        private Label label5;
        private Label label3;
        private Label label2;
        private TextBox LocationZ;
        private TextBox LocationY;
        private TextBox LocationX;
        private Button SaveButton;
        private GroupBox groupBox2;
        private Button ExportGenFile;
        private Button DeleteGenFile;
        private Label label9;
        private TextBox FilterGenName;
        private Button RefreshList;
        private Label FileCountLabel;
        private Button CreateNewGenFile;
        private Button OpenJSONButton;
        private Label label10;
        private LinkLabel TutorialLink;
        private Label label11;
        private TextBox FilterMonsterID;
        private Label MonsterCountLabel;
    }
}