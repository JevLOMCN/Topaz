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
            ConfigTabs.Size = new Size(539, 676);
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
            ChattingPage.Size = new Size(531, 648);
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
            FrontPage.Location = new Point(4, 24);
            FrontPage.Name = "FrontPage";
            FrontPage.Padding = new Padding(3);
            FrontPage.Size = new Size(531, 648);
            FrontPage.TabIndex = 1;
            FrontPage.Text = "Front";
            FrontPage.UseVisualStyleBackColor = true;
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
            GamePage.Size = new Size(531, 648);
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
            GatewayPage.Size = new Size(531, 648);
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
            WorldPage.Size = new Size(531, 648);
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
            ClientSize = new Size(539, 676);
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
    }
}