namespace Server_Console.Config
{
    partial class ConfigForm
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
            System.ComponentModel.ComponentResourceManager resources = new System.ComponentModel.ComponentResourceManager(typeof(ConfigForm));
            ConfigTabs = new TabControl();
            ChattingPage = new TabPage();
            AMsgBox = new TextBox();
            DumpPathBox = new TextBox();
            DumpSPathBox = new TextBox();
            MakeDumpBox = new TextBox();
            LogConsoleLevelBox = new TextBox();
            LogFileLevelBox = new TextBox();
            SendBufferBox = new TextBox();
            RecBufferBox = new TextBox();
            IOCPThreadBox = new TextBox();
            WorkerCountBox = new TextBox();
            ClientSocketBox = new TextBox();
            WorldSocketBox = new TextBox();
            WorldPortBox = new TextBox();
            WorldIPBox = new TextBox();
            PublicPortBox = new TextBox();
            PublicIPBox = new TextBox();
            ChattingLabel = new Label();
            FrontPage = new TabPage();
            textBox32 = new TextBox();
            textBox29 = new TextBox();
            textBox30 = new TextBox();
            textBox31 = new TextBox();
            textBox26 = new TextBox();
            textBox27 = new TextBox();
            textBox28 = new TextBox();
            textBox2 = new TextBox();
            textBox24 = new TextBox();
            textBox25 = new TextBox();
            textBox23 = new TextBox();
            textBox22 = new TextBox();
            textBox21 = new TextBox();
            textBox20 = new TextBox();
            textBox19 = new TextBox();
            textBox18 = new TextBox();
            textBox17 = new TextBox();
            textBox1 = new TextBox();
            textBox3 = new TextBox();
            textBox4 = new TextBox();
            textBox5 = new TextBox();
            textBox6 = new TextBox();
            textBox7 = new TextBox();
            textBox8 = new TextBox();
            textBox9 = new TextBox();
            textBox10 = new TextBox();
            textBox11 = new TextBox();
            textBox12 = new TextBox();
            textBox13 = new TextBox();
            textBox14 = new TextBox();
            textBox15 = new TextBox();
            textBox16 = new TextBox();
            FrontLabel = new Label();
            GamePage = new TabPage();
            GAMEMakeDumpBox = new TextBox();
            GAMEAssertBox = new TextBox();
            GAMEDumpPathBox = new TextBox();
            GAMEDumpServerPathBox = new TextBox();
            GAMELogConsoleBox = new TextBox();
            GAMELogFileBox = new TextBox();
            GAMESendBox = new TextBox();
            GAMERecBox = new TextBox();
            GAMEIOCPBox = new TextBox();
            GAMEWorkerBox = new TextBox();
            GAMEGatewaySocketBox = new TextBox();
            GAMEWorldPortBox = new TextBox();
            GAMEWorldIPBox = new TextBox();
            GameLabel = new Label();
            GatewayPage = new TabPage();
            GatewaySocket = new TextBox();
            GatewayAMessageBox = new TextBox();
            GatewayPath = new TextBox();
            GatewayServerPath = new TextBox();
            GatewayConsoleLevel = new TextBox();
            GatewayFileLevel = new TextBox();
            GatewaySend = new TextBox();
            GatewayRec = new TextBox();
            GatewayIOCP = new TextBox();
            GatewayWorker = new TextBox();
            GatewayConnectPort = new TextBox();
            GatewayConnectIP = new TextBox();
            GatewayPort = new TextBox();
            GatewayIP = new TextBox();
            GatewayMakeDump = new TextBox();
            GatewayLabel = new Label();
            WorldPage = new TabPage();
            WorldName = new TextBox();
            WorldPW = new TextBox();
            WorldUser = new TextBox();
            WorldPort1 = new TextBox();
            WorldIP = new TextBox();
            WorldAMessagebox = new TextBox();
            WorldDumpPath = new TextBox();
            WorldServerPath = new TextBox();
            WorldDump = new TextBox();
            WorldConsoleLevel = new TextBox();
            WorldFileLevel = new TextBox();
            WorldSend = new TextBox();
            WorldRec = new TextBox();
            WorldIOCP = new TextBox();
            WorldWorker = new TextBox();
            WorldConnectPort1 = new TextBox();
            WorldSocket = new TextBox();
            WorldConnectPort = new TextBox();
            WorldMaxCount = new TextBox();
            WorldPort = new TextBox();
            WorldID = new TextBox();
            WorldLabel = new Label();
            ConfigTabs.SuspendLayout();
            ChattingPage.SuspendLayout();
            FrontPage.SuspendLayout();
            GamePage.SuspendLayout();
            GatewayPage.SuspendLayout();
            WorldPage.SuspendLayout();
            SuspendLayout();
            // 
            // ConfigTabs
            // 
            ConfigTabs.Controls.Add(ChattingPage);
            ConfigTabs.Controls.Add(FrontPage);
            ConfigTabs.Controls.Add(GamePage);
            ConfigTabs.Controls.Add(GatewayPage);
            ConfigTabs.Controls.Add(WorldPage);
            ConfigTabs.Dock = DockStyle.Fill;
            ConfigTabs.Location = new Point(0, 0);
            ConfigTabs.Name = "ConfigTabs";
            ConfigTabs.SelectedIndex = 0;
            ConfigTabs.Size = new Size(609, 737);
            ConfigTabs.TabIndex = 0;
            ConfigTabs.SelectedIndexChanged += ConfigTabs_SelectedIndexChanged;
            // 
            // ChattingPage
            // 
            ChattingPage.Controls.Add(AMsgBox);
            ChattingPage.Controls.Add(DumpPathBox);
            ChattingPage.Controls.Add(DumpSPathBox);
            ChattingPage.Controls.Add(MakeDumpBox);
            ChattingPage.Controls.Add(LogConsoleLevelBox);
            ChattingPage.Controls.Add(LogFileLevelBox);
            ChattingPage.Controls.Add(SendBufferBox);
            ChattingPage.Controls.Add(RecBufferBox);
            ChattingPage.Controls.Add(IOCPThreadBox);
            ChattingPage.Controls.Add(WorkerCountBox);
            ChattingPage.Controls.Add(ClientSocketBox);
            ChattingPage.Controls.Add(WorldSocketBox);
            ChattingPage.Controls.Add(WorldPortBox);
            ChattingPage.Controls.Add(WorldIPBox);
            ChattingPage.Controls.Add(PublicPortBox);
            ChattingPage.Controls.Add(PublicIPBox);
            ChattingPage.Controls.Add(ChattingLabel);
            ChattingPage.Location = new Point(4, 24);
            ChattingPage.Name = "ChattingPage";
            ChattingPage.Padding = new Padding(3);
            ChattingPage.Size = new Size(601, 709);
            ChattingPage.TabIndex = 0;
            ChattingPage.Text = "Chatting";
            ChattingPage.UseVisualStyleBackColor = true;
            // 
            // AMsgBox
            // 
            AMsgBox.Location = new Point(117, 461);
            AMsgBox.Name = "AMsgBox";
            AMsgBox.Size = new Size(43, 23);
            AMsgBox.TabIndex = 16;
            // 
            // DumpPathBox
            // 
            DumpPathBox.Location = new Point(80, 429);
            DumpPathBox.Name = "DumpPathBox";
            DumpPathBox.Size = new Size(177, 23);
            DumpPathBox.TabIndex = 15;
            // 
            // DumpSPathBox
            // 
            DumpSPathBox.Location = new Point(110, 400);
            DumpSPathBox.Name = "DumpSPathBox";
            DumpSPathBox.Size = new Size(177, 23);
            DumpSPathBox.TabIndex = 14;
            // 
            // MakeDumpBox
            // 
            MakeDumpBox.Location = new Point(80, 370);
            MakeDumpBox.Name = "MakeDumpBox";
            MakeDumpBox.Size = new Size(43, 23);
            MakeDumpBox.TabIndex = 13;
            // 
            // LogConsoleLevelBox
            // 
            LogConsoleLevelBox.Location = new Point(105, 339);
            LogConsoleLevelBox.Name = "LogConsoleLevelBox";
            LogConsoleLevelBox.Size = new Size(43, 23);
            LogConsoleLevelBox.TabIndex = 12;
            // 
            // LogFileLevelBox
            // 
            LogFileLevelBox.Location = new Point(85, 310);
            LogFileLevelBox.Name = "LogFileLevelBox";
            LogFileLevelBox.Size = new Size(43, 23);
            LogFileLevelBox.TabIndex = 11;
            // 
            // SendBufferBox
            // 
            SendBufferBox.Location = new Point(100, 281);
            SendBufferBox.Name = "SendBufferBox";
            SendBufferBox.Size = new Size(69, 23);
            SendBufferBox.TabIndex = 10;
            // 
            // RecBufferBox
            // 
            RecBufferBox.Location = new Point(100, 252);
            RecBufferBox.Name = "RecBufferBox";
            RecBufferBox.Size = new Size(69, 23);
            RecBufferBox.TabIndex = 9;
            // 
            // IOCPThreadBox
            // 
            IOCPThreadBox.Location = new Point(117, 220);
            IOCPThreadBox.Name = "IOCPThreadBox";
            IOCPThreadBox.Size = new Size(43, 23);
            IOCPThreadBox.TabIndex = 8;
            // 
            // WorkerCountBox
            // 
            WorkerCountBox.Location = new Point(117, 191);
            WorkerCountBox.Name = "WorkerCountBox";
            WorkerCountBox.Size = new Size(43, 23);
            WorkerCountBox.TabIndex = 7;
            // 
            // ClientSocketBox
            // 
            ClientSocketBox.Location = new Point(145, 162);
            ClientSocketBox.Name = "ClientSocketBox";
            ClientSocketBox.Size = new Size(43, 23);
            ClientSocketBox.TabIndex = 6;
            // 
            // WorldSocketBox
            // 
            WorldSocketBox.Location = new Point(244, 130);
            WorldSocketBox.Name = "WorldSocketBox";
            WorldSocketBox.Size = new Size(43, 23);
            WorldSocketBox.TabIndex = 5;
            // 
            // WorldPortBox
            // 
            WorldPortBox.Location = new Point(158, 100);
            WorldPortBox.Name = "WorldPortBox";
            WorldPortBox.Size = new Size(76, 23);
            WorldPortBox.TabIndex = 4;
            // 
            // WorldIPBox
            // 
            WorldIPBox.Location = new Point(145, 70);
            WorldIPBox.Name = "WorldIPBox";
            WorldIPBox.Size = new Size(100, 23);
            WorldIPBox.TabIndex = 3;
            // 
            // PublicPortBox
            // 
            PublicPortBox.Location = new Point(72, 40);
            PublicPortBox.Name = "PublicPortBox";
            PublicPortBox.Size = new Size(76, 23);
            PublicPortBox.TabIndex = 2;
            // 
            // PublicIPBox
            // 
            PublicIPBox.Location = new Point(60, 10);
            PublicIPBox.Name = "PublicIPBox";
            PublicIPBox.Size = new Size(100, 23);
            PublicIPBox.TabIndex = 1;
            // 
            // ChattingLabel
            // 
            ChattingLabel.AutoSize = true;
            ChattingLabel.Location = new Point(0, 0);
            ChattingLabel.Name = "ChattingLabel";
            ChattingLabel.Size = new Size(238, 480);
            ChattingLabel.TabIndex = 0;
            ChattingLabel.Text = resources.GetString("ChattingLabel.Text");
            // 
            // FrontPage
            // 
            FrontPage.Controls.Add(textBox32);
            FrontPage.Controls.Add(textBox29);
            FrontPage.Controls.Add(textBox30);
            FrontPage.Controls.Add(textBox31);
            FrontPage.Controls.Add(textBox26);
            FrontPage.Controls.Add(textBox27);
            FrontPage.Controls.Add(textBox28);
            FrontPage.Controls.Add(textBox2);
            FrontPage.Controls.Add(textBox24);
            FrontPage.Controls.Add(textBox25);
            FrontPage.Controls.Add(textBox23);
            FrontPage.Controls.Add(textBox22);
            FrontPage.Controls.Add(textBox21);
            FrontPage.Controls.Add(textBox20);
            FrontPage.Controls.Add(textBox19);
            FrontPage.Controls.Add(textBox18);
            FrontPage.Controls.Add(textBox17);
            FrontPage.Controls.Add(textBox1);
            FrontPage.Controls.Add(textBox3);
            FrontPage.Controls.Add(textBox4);
            FrontPage.Controls.Add(textBox5);
            FrontPage.Controls.Add(textBox6);
            FrontPage.Controls.Add(textBox7);
            FrontPage.Controls.Add(textBox8);
            FrontPage.Controls.Add(textBox9);
            FrontPage.Controls.Add(textBox10);
            FrontPage.Controls.Add(textBox11);
            FrontPage.Controls.Add(textBox12);
            FrontPage.Controls.Add(textBox13);
            FrontPage.Controls.Add(textBox14);
            FrontPage.Controls.Add(textBox15);
            FrontPage.Controls.Add(textBox16);
            FrontPage.Controls.Add(FrontLabel);
            FrontPage.Location = new Point(4, 24);
            FrontPage.Name = "FrontPage";
            FrontPage.Padding = new Padding(3);
            FrontPage.Size = new Size(601, 709);
            FrontPage.TabIndex = 1;
            FrontPage.Text = "Front";
            FrontPage.UseVisualStyleBackColor = true;
            // 
            // textBox32
            // 
            textBox32.Location = new Point(245, 673);
            textBox32.Name = "textBox32";
            textBox32.Size = new Size(122, 23);
            textBox32.TabIndex = 50;
            // 
            // textBox29
            // 
            textBox29.Location = new Point(160, 644);
            textBox29.Name = "textBox29";
            textBox29.Size = new Size(122, 23);
            textBox29.TabIndex = 49;
            // 
            // textBox30
            // 
            textBox30.Location = new Point(134, 614);
            textBox30.Name = "textBox30";
            textBox30.Size = new Size(122, 23);
            textBox30.TabIndex = 48;
            // 
            // textBox31
            // 
            textBox31.Location = new Point(53, 583);
            textBox31.Name = "textBox31";
            textBox31.Size = new Size(122, 23);
            textBox31.TabIndex = 47;
            // 
            // textBox26
            // 
            textBox26.Location = new Point(64, 554);
            textBox26.Name = "textBox26";
            textBox26.Size = new Size(122, 23);
            textBox26.TabIndex = 46;
            // 
            // textBox27
            // 
            textBox27.Location = new Point(58, 526);
            textBox27.Name = "textBox27";
            textBox27.Size = new Size(122, 23);
            textBox27.TabIndex = 45;
            // 
            // textBox28
            // 
            textBox28.Location = new Point(68, 494);
            textBox28.Name = "textBox28";
            textBox28.Size = new Size(122, 23);
            textBox28.TabIndex = 44;
            // 
            // textBox2
            // 
            textBox2.Location = new Point(110, 464);
            textBox2.Name = "textBox2";
            textBox2.Size = new Size(122, 23);
            textBox2.TabIndex = 43;
            // 
            // textBox24
            // 
            textBox24.Location = new Point(94, 435);
            textBox24.Name = "textBox24";
            textBox24.Size = new Size(122, 23);
            textBox24.TabIndex = 42;
            // 
            // textBox25
            // 
            textBox25.Location = new Point(104, 406);
            textBox25.Name = "textBox25";
            textBox25.Size = new Size(122, 23);
            textBox25.TabIndex = 41;
            // 
            // textBox23
            // 
            textBox23.Location = new Point(464, 344);
            textBox23.Name = "textBox23";
            textBox23.Size = new Size(122, 23);
            textBox23.TabIndex = 40;
            // 
            // textBox22
            // 
            textBox22.Location = new Point(457, 312);
            textBox22.Name = "textBox22";
            textBox22.Size = new Size(122, 23);
            textBox22.TabIndex = 39;
            // 
            // textBox21
            // 
            textBox21.Location = new Point(374, 344);
            textBox21.Name = "textBox21";
            textBox21.Size = new Size(64, 23);
            textBox21.TabIndex = 38;
            // 
            // textBox20
            // 
            textBox20.Location = new Point(372, 312);
            textBox20.Name = "textBox20";
            textBox20.Size = new Size(64, 23);
            textBox20.TabIndex = 37;
            // 
            // textBox19
            // 
            textBox19.Location = new Point(365, 283);
            textBox19.Name = "textBox19";
            textBox19.Size = new Size(64, 23);
            textBox19.TabIndex = 36;
            // 
            // textBox18
            // 
            textBox18.Location = new Point(257, 344);
            textBox18.Name = "textBox18";
            textBox18.Size = new Size(52, 23);
            textBox18.TabIndex = 35;
            // 
            // textBox17
            // 
            textBox17.Location = new Point(254, 312);
            textBox17.Name = "textBox17";
            textBox17.Size = new Size(52, 23);
            textBox17.TabIndex = 34;
            // 
            // textBox1
            // 
            textBox1.Location = new Point(245, 283);
            textBox1.Name = "textBox1";
            textBox1.Size = new Size(64, 23);
            textBox1.TabIndex = 33;
            // 
            // textBox3
            // 
            textBox3.Location = new Point(457, 283);
            textBox3.Name = "textBox3";
            textBox3.Size = new Size(122, 23);
            textBox3.TabIndex = 31;
            // 
            // textBox4
            // 
            textBox4.Location = new Point(126, 344);
            textBox4.Name = "textBox4";
            textBox4.Size = new Size(97, 23);
            textBox4.TabIndex = 30;
            // 
            // textBox5
            // 
            textBox5.Location = new Point(116, 312);
            textBox5.Name = "textBox5";
            textBox5.Size = new Size(93, 23);
            textBox5.TabIndex = 29;
            // 
            // textBox6
            // 
            textBox6.Location = new Point(116, 283);
            textBox6.Name = "textBox6";
            textBox6.Size = new Size(93, 23);
            textBox6.TabIndex = 28;
            // 
            // textBox7
            // 
            textBox7.Location = new Point(53, 373);
            textBox7.Name = "textBox7";
            textBox7.Size = new Size(116, 23);
            textBox7.TabIndex = 27;
            // 
            // textBox8
            // 
            textBox8.Location = new Point(90, 254);
            textBox8.Name = "textBox8";
            textBox8.Size = new Size(69, 23);
            textBox8.TabIndex = 26;
            // 
            // textBox9
            // 
            textBox9.Location = new Point(133, 225);
            textBox9.Name = "textBox9";
            textBox9.Size = new Size(76, 23);
            textBox9.TabIndex = 25;
            // 
            // textBox10
            // 
            textBox10.Location = new Point(157, 196);
            textBox10.Name = "textBox10";
            textBox10.Size = new Size(90, 23);
            textBox10.TabIndex = 24;
            // 
            // textBox11
            // 
            textBox11.Location = new Point(79, 162);
            textBox11.Name = "textBox11";
            textBox11.Size = new Size(66, 23);
            textBox11.TabIndex = 23;
            // 
            // textBox12
            // 
            textBox12.Location = new Point(79, 133);
            textBox12.Name = "textBox12";
            textBox12.Size = new Size(43, 23);
            textBox12.TabIndex = 22;
            // 
            // textBox13
            // 
            textBox13.Location = new Point(46, 104);
            textBox13.Name = "textBox13";
            textBox13.Size = new Size(76, 23);
            textBox13.TabIndex = 21;
            // 
            // textBox14
            // 
            textBox14.Location = new Point(90, 73);
            textBox14.Name = "textBox14";
            textBox14.Size = new Size(100, 23);
            textBox14.TabIndex = 20;
            // 
            // textBox15
            // 
            textBox15.Location = new Point(133, 44);
            textBox15.Name = "textBox15";
            textBox15.Size = new Size(76, 23);
            textBox15.TabIndex = 19;
            // 
            // textBox16
            // 
            textBox16.Location = new Point(90, 15);
            textBox16.Name = "textBox16";
            textBox16.Size = new Size(100, 23);
            textBox16.TabIndex = 18;
            // 
            // FrontLabel
            // 
            FrontLabel.AutoSize = true;
            FrontLabel.Dock = DockStyle.Left;
            FrontLabel.Location = new Point(3, 3);
            FrontLabel.Name = "FrontLabel";
            FrontLabel.Size = new Size(461, 690);
            FrontLabel.TabIndex = 17;
            FrontLabel.Text = resources.GetString("FrontLabel.Text");
            // 
            // GamePage
            // 
            GamePage.Controls.Add(GAMEMakeDumpBox);
            GamePage.Controls.Add(GAMEAssertBox);
            GamePage.Controls.Add(GAMEDumpPathBox);
            GamePage.Controls.Add(GAMEDumpServerPathBox);
            GamePage.Controls.Add(GAMELogConsoleBox);
            GamePage.Controls.Add(GAMELogFileBox);
            GamePage.Controls.Add(GAMESendBox);
            GamePage.Controls.Add(GAMERecBox);
            GamePage.Controls.Add(GAMEIOCPBox);
            GamePage.Controls.Add(GAMEWorkerBox);
            GamePage.Controls.Add(GAMEGatewaySocketBox);
            GamePage.Controls.Add(GAMEWorldPortBox);
            GamePage.Controls.Add(GAMEWorldIPBox);
            GamePage.Controls.Add(GameLabel);
            GamePage.Location = new Point(4, 24);
            GamePage.Name = "GamePage";
            GamePage.Size = new Size(601, 709);
            GamePage.TabIndex = 2;
            GamePage.Text = "Game";
            GamePage.UseVisualStyleBackColor = true;
            // 
            // GAMEMakeDumpBox
            // 
            GAMEMakeDumpBox.Location = new Point(80, 280);
            GAMEMakeDumpBox.Name = "GAMEMakeDumpBox";
            GAMEMakeDumpBox.Size = new Size(43, 23);
            GAMEMakeDumpBox.TabIndex = 30;
            // 
            // GAMEAssertBox
            // 
            GAMEAssertBox.Location = new Point(120, 370);
            GAMEAssertBox.Name = "GAMEAssertBox";
            GAMEAssertBox.Size = new Size(43, 23);
            GAMEAssertBox.TabIndex = 28;
            // 
            // GAMEDumpPathBox
            // 
            GAMEDumpPathBox.Location = new Point(80, 340);
            GAMEDumpPathBox.Name = "GAMEDumpPathBox";
            GAMEDumpPathBox.Size = new Size(177, 23);
            GAMEDumpPathBox.TabIndex = 27;
            // 
            // GAMEDumpServerPathBox
            // 
            GAMEDumpServerPathBox.Location = new Point(110, 310);
            GAMEDumpServerPathBox.Name = "GAMEDumpServerPathBox";
            GAMEDumpServerPathBox.Size = new Size(177, 23);
            GAMEDumpServerPathBox.TabIndex = 26;
            // 
            // GAMELogConsoleBox
            // 
            GAMELogConsoleBox.Location = new Point(110, 250);
            GAMELogConsoleBox.Name = "GAMELogConsoleBox";
            GAMELogConsoleBox.Size = new Size(43, 23);
            GAMELogConsoleBox.TabIndex = 25;
            // 
            // GAMELogFileBox
            // 
            GAMELogFileBox.Location = new Point(84, 218);
            GAMELogFileBox.Name = "GAMELogFileBox";
            GAMELogFileBox.Size = new Size(43, 23);
            GAMELogFileBox.TabIndex = 24;
            // 
            // GAMESendBox
            // 
            GAMESendBox.Location = new Point(95, 190);
            GAMESendBox.Name = "GAMESendBox";
            GAMESendBox.Size = new Size(43, 23);
            GAMESendBox.TabIndex = 23;
            // 
            // GAMERecBox
            // 
            GAMERecBox.Location = new Point(95, 160);
            GAMERecBox.Name = "GAMERecBox";
            GAMERecBox.Size = new Size(69, 23);
            GAMERecBox.TabIndex = 22;
            // 
            // GAMEIOCPBox
            // 
            GAMEIOCPBox.Location = new Point(114, 130);
            GAMEIOCPBox.Name = "GAMEIOCPBox";
            GAMEIOCPBox.Size = new Size(69, 23);
            GAMEIOCPBox.TabIndex = 21;
            // 
            // GAMEWorkerBox
            // 
            GAMEWorkerBox.Location = new Point(114, 100);
            GAMEWorkerBox.Name = "GAMEWorkerBox";
            GAMEWorkerBox.Size = new Size(43, 23);
            GAMEWorkerBox.TabIndex = 20;
            // 
            // GAMEGatewaySocketBox
            // 
            GAMEGatewaySocketBox.Location = new Point(160, 70);
            GAMEGatewaySocketBox.Name = "GAMEGatewaySocketBox";
            GAMEGatewaySocketBox.Size = new Size(43, 23);
            GAMEGatewaySocketBox.TabIndex = 19;
            // 
            // GAMEWorldPortBox
            // 
            GAMEWorldPortBox.Location = new Point(154, 40);
            GAMEWorldPortBox.Name = "GAMEWorldPortBox";
            GAMEWorldPortBox.Size = new Size(76, 23);
            GAMEWorldPortBox.TabIndex = 18;
            // 
            // GAMEWorldIPBox
            // 
            GAMEWorldIPBox.Location = new Point(144, 10);
            GAMEWorldIPBox.Name = "GAMEWorldIPBox";
            GAMEWorldIPBox.Size = new Size(100, 23);
            GAMEWorldIPBox.TabIndex = 17;
            // 
            // GameLabel
            // 
            GameLabel.AutoSize = true;
            GameLabel.Dock = DockStyle.Left;
            GameLabel.Location = new Point(0, 0);
            GameLabel.Name = "GameLabel";
            GameLabel.Size = new Size(155, 390);
            GameLabel.TabIndex = 29;
            GameLabel.Text = resources.GetString("GameLabel.Text");
            // 
            // GatewayPage
            // 
            GatewayPage.Controls.Add(GatewaySocket);
            GatewayPage.Controls.Add(GatewayAMessageBox);
            GatewayPage.Controls.Add(GatewayPath);
            GatewayPage.Controls.Add(GatewayServerPath);
            GatewayPage.Controls.Add(GatewayConsoleLevel);
            GatewayPage.Controls.Add(GatewayFileLevel);
            GatewayPage.Controls.Add(GatewaySend);
            GatewayPage.Controls.Add(GatewayRec);
            GatewayPage.Controls.Add(GatewayIOCP);
            GatewayPage.Controls.Add(GatewayWorker);
            GatewayPage.Controls.Add(GatewayConnectPort);
            GatewayPage.Controls.Add(GatewayConnectIP);
            GatewayPage.Controls.Add(GatewayPort);
            GatewayPage.Controls.Add(GatewayIP);
            GatewayPage.Controls.Add(GatewayMakeDump);
            GatewayPage.Controls.Add(GatewayLabel);
            GatewayPage.Location = new Point(4, 24);
            GatewayPage.Name = "GatewayPage";
            GatewayPage.Size = new Size(601, 709);
            GatewayPage.TabIndex = 3;
            GatewayPage.Text = "Gateway";
            GatewayPage.UseVisualStyleBackColor = true;
            // 
            // GatewaySocket
            // 
            GatewaySocket.Location = new Point(146, 132);
            GatewaySocket.Name = "GatewaySocket";
            GatewaySocket.Size = new Size(43, 23);
            GatewaySocket.TabIndex = 34;
            // 
            // GatewayAMessageBox
            // 
            GatewayAMessageBox.Location = new Point(114, 432);
            GatewayAMessageBox.Name = "GatewayAMessageBox";
            GatewayAMessageBox.Size = new Size(43, 23);
            GatewayAMessageBox.TabIndex = 33;
            // 
            // GatewayPath
            // 
            GatewayPath.Location = new Point(75, 402);
            GatewayPath.Name = "GatewayPath";
            GatewayPath.Size = new Size(177, 23);
            GatewayPath.TabIndex = 32;
            // 
            // GatewayServerPath
            // 
            GatewayServerPath.Location = new Point(110, 372);
            GatewayServerPath.Name = "GatewayServerPath";
            GatewayServerPath.Size = new Size(177, 23);
            GatewayServerPath.TabIndex = 31;
            // 
            // GatewayConsoleLevel
            // 
            GatewayConsoleLevel.Location = new Point(110, 312);
            GatewayConsoleLevel.Name = "GatewayConsoleLevel";
            GatewayConsoleLevel.Size = new Size(43, 23);
            GatewayConsoleLevel.TabIndex = 29;
            // 
            // GatewayFileLevel
            // 
            GatewayFileLevel.Location = new Point(84, 282);
            GatewayFileLevel.Name = "GatewayFileLevel";
            GatewayFileLevel.Size = new Size(43, 23);
            GatewayFileLevel.TabIndex = 28;
            // 
            // GatewaySend
            // 
            GatewaySend.Location = new Point(98, 256);
            GatewaySend.Name = "GatewaySend";
            GatewaySend.Size = new Size(69, 23);
            GatewaySend.TabIndex = 27;
            // 
            // GatewayRec
            // 
            GatewayRec.Location = new Point(98, 227);
            GatewayRec.Name = "GatewayRec";
            GatewayRec.Size = new Size(69, 23);
            GatewayRec.TabIndex = 26;
            // 
            // GatewayIOCP
            // 
            GatewayIOCP.Location = new Point(116, 197);
            GatewayIOCP.Name = "GatewayIOCP";
            GatewayIOCP.Size = new Size(43, 23);
            GatewayIOCP.TabIndex = 25;
            // 
            // GatewayWorker
            // 
            GatewayWorker.Location = new Point(116, 167);
            GatewayWorker.Name = "GatewayWorker";
            GatewayWorker.Size = new Size(43, 23);
            GatewayWorker.TabIndex = 24;
            // 
            // GatewayConnectPort
            // 
            GatewayConnectPort.Location = new Point(156, 100);
            GatewayConnectPort.Name = "GatewayConnectPort";
            GatewayConnectPort.Size = new Size(80, 23);
            GatewayConnectPort.TabIndex = 23;
            // 
            // GatewayConnectIP
            // 
            GatewayConnectIP.Location = new Point(140, 71);
            GatewayConnectIP.Name = "GatewayConnectIP";
            GatewayConnectIP.Size = new Size(96, 23);
            GatewayConnectIP.TabIndex = 22;
            // 
            // GatewayPort
            // 
            GatewayPort.Location = new Point(73, 43);
            GatewayPort.Name = "GatewayPort";
            GatewayPort.Size = new Size(76, 23);
            GatewayPort.TabIndex = 21;
            // 
            // GatewayIP
            // 
            GatewayIP.Location = new Point(59, 13);
            GatewayIP.Name = "GatewayIP";
            GatewayIP.Size = new Size(100, 23);
            GatewayIP.TabIndex = 20;
            // 
            // GatewayMakeDump
            // 
            GatewayMakeDump.Location = new Point(78, 342);
            GatewayMakeDump.Name = "GatewayMakeDump";
            GatewayMakeDump.Size = new Size(43, 23);
            GatewayMakeDump.TabIndex = 30;
            // 
            // GatewayLabel
            // 
            GatewayLabel.AutoSize = true;
            GatewayLabel.Dock = DockStyle.Left;
            GatewayLabel.Location = new Point(0, 0);
            GatewayLabel.Name = "GatewayLabel";
            GatewayLabel.Size = new Size(150, 450);
            GatewayLabel.TabIndex = 17;
            GatewayLabel.Text = resources.GetString("GatewayLabel.Text");
            // 
            // WorldPage
            // 
            WorldPage.Controls.Add(WorldName);
            WorldPage.Controls.Add(WorldPW);
            WorldPage.Controls.Add(WorldUser);
            WorldPage.Controls.Add(WorldPort1);
            WorldPage.Controls.Add(WorldIP);
            WorldPage.Controls.Add(WorldAMessagebox);
            WorldPage.Controls.Add(WorldDumpPath);
            WorldPage.Controls.Add(WorldServerPath);
            WorldPage.Controls.Add(WorldDump);
            WorldPage.Controls.Add(WorldConsoleLevel);
            WorldPage.Controls.Add(WorldFileLevel);
            WorldPage.Controls.Add(WorldSend);
            WorldPage.Controls.Add(WorldRec);
            WorldPage.Controls.Add(WorldIOCP);
            WorldPage.Controls.Add(WorldWorker);
            WorldPage.Controls.Add(WorldConnectPort1);
            WorldPage.Controls.Add(WorldSocket);
            WorldPage.Controls.Add(WorldConnectPort);
            WorldPage.Controls.Add(WorldMaxCount);
            WorldPage.Controls.Add(WorldPort);
            WorldPage.Controls.Add(WorldID);
            WorldPage.Controls.Add(WorldLabel);
            WorldPage.Location = new Point(4, 24);
            WorldPage.Name = "WorldPage";
            WorldPage.Size = new Size(601, 709);
            WorldPage.TabIndex = 4;
            WorldPage.Text = "World";
            WorldPage.UseVisualStyleBackColor = true;
            // 
            // WorldName
            // 
            WorldName.Location = new Point(100, 612);
            WorldName.Name = "WorldName";
            WorldName.Size = new Size(116, 23);
            WorldName.TabIndex = 39;
            // 
            // WorldPW
            // 
            WorldPW.Location = new Point(86, 583);
            WorldPW.Name = "WorldPW";
            WorldPW.Size = new Size(130, 23);
            WorldPW.TabIndex = 38;
            // 
            // WorldUser
            // 
            WorldUser.Location = new Point(90, 554);
            WorldUser.Name = "WorldUser";
            WorldUser.Size = new Size(110, 23);
            WorldUser.TabIndex = 37;
            // 
            // WorldPort1
            // 
            WorldPort1.Location = new Point(86, 524);
            WorldPort1.Name = "WorldPort1";
            WorldPort1.Size = new Size(88, 23);
            WorldPort1.TabIndex = 36;
            // 
            // WorldIP
            // 
            WorldIP.Location = new Point(74, 494);
            WorldIP.Name = "WorldIP";
            WorldIP.Size = new Size(100, 23);
            WorldIP.TabIndex = 35;
            // 
            // WorldAMessagebox
            // 
            WorldAMessagebox.Location = new Point(116, 464);
            WorldAMessagebox.Name = "WorldAMessagebox";
            WorldAMessagebox.Size = new Size(33, 23);
            WorldAMessagebox.TabIndex = 34;
            // 
            // WorldDumpPath
            // 
            WorldDumpPath.Location = new Point(74, 430);
            WorldDumpPath.Name = "WorldDumpPath";
            WorldDumpPath.Size = new Size(142, 23);
            WorldDumpPath.TabIndex = 33;
            // 
            // WorldServerPath
            // 
            WorldServerPath.Location = new Point(114, 400);
            WorldServerPath.Name = "WorldServerPath";
            WorldServerPath.Size = new Size(148, 23);
            WorldServerPath.TabIndex = 32;
            // 
            // WorldDump
            // 
            WorldDump.Location = new Point(77, 371);
            WorldDump.Name = "WorldDump";
            WorldDump.Size = new Size(46, 23);
            WorldDump.TabIndex = 31;
            // 
            // WorldConsoleLevel
            // 
            WorldConsoleLevel.Location = new Point(114, 342);
            WorldConsoleLevel.Name = "WorldConsoleLevel";
            WorldConsoleLevel.Size = new Size(35, 23);
            WorldConsoleLevel.TabIndex = 30;
            // 
            // WorldFileLevel
            // 
            WorldFileLevel.Location = new Point(87, 313);
            WorldFileLevel.Name = "WorldFileLevel";
            WorldFileLevel.Size = new Size(36, 23);
            WorldFileLevel.TabIndex = 29;
            // 
            // WorldSend
            // 
            WorldSend.Location = new Point(100, 284);
            WorldSend.Name = "WorldSend";
            WorldSend.Size = new Size(100, 23);
            WorldSend.TabIndex = 28;
            // 
            // WorldRec
            // 
            WorldRec.Location = new Point(100, 255);
            WorldRec.Name = "WorldRec";
            WorldRec.Size = new Size(100, 23);
            WorldRec.TabIndex = 27;
            // 
            // WorldIOCP
            // 
            WorldIOCP.Location = new Point(114, 217);
            WorldIOCP.Name = "WorldIOCP";
            WorldIOCP.Size = new Size(45, 23);
            WorldIOCP.TabIndex = 26;
            // 
            // WorldWorker
            // 
            WorldWorker.Location = new Point(114, 188);
            WorldWorker.Name = "WorldWorker";
            WorldWorker.Size = new Size(45, 23);
            WorldWorker.TabIndex = 25;
            // 
            // WorldConnectPort1
            // 
            WorldConnectPort1.Location = new Point(162, 159);
            WorldConnectPort1.Name = "WorldConnectPort1";
            WorldConnectPort1.Size = new Size(78, 23);
            WorldConnectPort1.TabIndex = 24;
            // 
            // WorldSocket
            // 
            WorldSocket.Location = new Point(193, 130);
            WorldSocket.Name = "WorldSocket";
            WorldSocket.Size = new Size(47, 23);
            WorldSocket.TabIndex = 23;
            // 
            // WorldConnectPort
            // 
            WorldConnectPort.Location = new Point(162, 101);
            WorldConnectPort.Name = "WorldConnectPort";
            WorldConnectPort.Size = new Size(78, 23);
            WorldConnectPort.TabIndex = 22;
            // 
            // WorldMaxCount
            // 
            WorldMaxCount.Location = new Point(177, 72);
            WorldMaxCount.Name = "WorldMaxCount";
            WorldMaxCount.Size = new Size(39, 23);
            WorldMaxCount.TabIndex = 21;
            // 
            // WorldPort
            // 
            WorldPort.Location = new Point(148, 43);
            WorldPort.Name = "WorldPort";
            WorldPort.Size = new Size(92, 23);
            WorldPort.TabIndex = 20;
            // 
            // WorldID
            // 
            WorldID.Location = new Point(60, 14);
            WorldID.Name = "WorldID";
            WorldID.Size = new Size(38, 23);
            WorldID.TabIndex = 19;
            // 
            // WorldLabel
            // 
            WorldLabel.AutoSize = true;
            WorldLabel.Dock = DockStyle.Left;
            WorldLabel.Location = new Point(0, 0);
            WorldLabel.Name = "WorldLabel";
            WorldLabel.Size = new Size(187, 630);
            WorldLabel.TabIndex = 18;
            WorldLabel.Text = resources.GetString("WorldLabel.Text");
            // 
            // ConfigForm
            // 
            AutoScaleDimensions = new SizeF(7F, 15F);
            AutoScaleMode = AutoScaleMode.Font;
            ClientSize = new Size(609, 737);
            Controls.Add(ConfigTabs);
            Icon = (Icon)resources.GetObject("$this.Icon");
            MaximizeBox = false;
            MinimizeBox = false;
            Name = "ConfigForm";
            StartPosition = FormStartPosition.CenterScreen;
            Text = "Server Configs";
            ConfigTabs.ResumeLayout(false);
            ChattingPage.ResumeLayout(false);
            ChattingPage.PerformLayout();
            FrontPage.ResumeLayout(false);
            FrontPage.PerformLayout();
            GamePage.ResumeLayout(false);
            GamePage.PerformLayout();
            GatewayPage.ResumeLayout(false);
            GatewayPage.PerformLayout();
            WorldPage.ResumeLayout(false);
            WorldPage.PerformLayout();
            ResumeLayout(false);
        }

        #endregion

        private TabControl ConfigTabs;
        private TabPage ChattingPage;
        private TabPage FrontPage;
        private TabPage GamePage;
        private TabPage GatewayPage;
        private TabPage WorldPage;
        private Label ChattingLabel;
        private TextBox PublicIPBox;
        private TextBox PublicPortBox;
        private TextBox WorldIPBox;
        private TextBox WorldPortBox;
        private TextBox WorldSocketBox;
        private TextBox WorkerCountBox;
        private TextBox ClientSocketBox;
        private TextBox RecBufferBox;
        private TextBox IOCPThreadBox;
        private TextBox SendBufferBox;
        private TextBox AMsgBox;
        private TextBox DumpPathBox;
        private TextBox DumpSPathBox;
        private TextBox MakeDumpBox;
        private TextBox LogConsoleLevelBox;
        private TextBox LogFileLevelBox;
        private TextBox GAMEAssertBox;
        private TextBox GAMEDumpPathBox;
        private TextBox GAMEDumpServerPathBox;
        private TextBox GAMELogConsoleBox;
        private TextBox GAMELogFileBox;
        private TextBox GAMESendBox;
        private TextBox GAMERecBox;
        private TextBox GAMEIOCPBox;
        private TextBox GAMEWorkerBox;
        private TextBox GAMEGatewaySocketBox;
        private TextBox GAMEWorldPortBox;
        private TextBox GAMEWorldIPBox;
        private Label GameLabel;
        private TextBox GAMEMakeDumpBox;
        private TextBox GatewayAMessageBox;
        private TextBox GatewayPath;
        private TextBox GatewayServerPath;
        private TextBox GatewayConsoleLevel;
        private TextBox GatewayFileLevel;
        private TextBox GatewaySend;
        private TextBox GatewayRec;
        private TextBox GatewayIOCP;
        private TextBox GatewayWorker;
        private TextBox GatewayConnectPort;
        private TextBox GatewayConnectIP;
        private TextBox GatewayPort;
        private TextBox GatewayIP;
        private TextBox GatewayMakeDump;
        private Label GatewayLabel;
        private TextBox GatewaySocket;
        private Label WorldLabel;
        private TextBox WorldName;
        private TextBox WorldPW;
        private TextBox WorldUser;
        private TextBox WorldPort1;
        private TextBox WorldIP;
        private TextBox WorldAMessagebox;
        private TextBox WorldDumpPath;
        private TextBox WorldServerPath;
        private TextBox WorldDump;
        private TextBox WorldConsoleLevel;
        private TextBox WorldFileLevel;
        private TextBox WorldSend;
        private TextBox WorldRec;
        private TextBox WorldIOCP;
        private TextBox WorldWorker;
        private TextBox WorldConnectPort1;
        private TextBox WorldSocket;
        private TextBox WorldConnectPort;
        private TextBox WorldMaxCount;
        private TextBox WorldPort;
        private TextBox WorldID;
        private TextBox textBox1;
        private TextBox textBox3;
        private TextBox textBox4;
        private TextBox textBox5;
        private TextBox textBox6;
        private TextBox textBox7;
        private TextBox textBox8;
        private TextBox textBox9;
        private TextBox textBox10;
        private TextBox textBox11;
        private TextBox textBox12;
        private TextBox textBox13;
        private TextBox textBox14;
        private TextBox textBox15;
        private TextBox textBox16;
        private Label FrontLabel;
        private TextBox textBox23;
        private TextBox textBox22;
        private TextBox textBox21;
        private TextBox textBox20;
        private TextBox textBox19;
        private TextBox textBox18;
        private TextBox textBox17;
        private TextBox textBox26;
        private TextBox textBox27;
        private TextBox textBox28;
        private TextBox textBox2;
        private TextBox textBox24;
        private TextBox textBox25;
        private TextBox textBox32;
        private TextBox textBox29;
        private TextBox textBox30;
        private TextBox textBox31;
    }
}