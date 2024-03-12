using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Data;
using System.Drawing;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using System.Windows.Forms;
using Newtonsoft.Json.Linq;


namespace Server_Console.Config
{
    public partial class ConfigForm : Form
    {
        private Dictionary<TabPage, Size> originalTabSizes;
        public ConfigForm()
        {
            InitializeComponent();
            SetTabSize();
            LoadConfigs();
        }

        #region Page Resize
        private void SetTabSize()
        {
            originalTabSizes = new Dictionary<TabPage, Size>();

            //Set the size for each Page
            SetTabPageSize(ChattingPage, 555, 566);
            SetTabPageSize(FrontPage, 555, 715);
            SetTabPageSize(GamePage, 555, 475);
            SetTabPageSize(GatewayPage, 555, 540);
            SetTabPageSize(WorldPage, 555, 715);
        }

        private void SetTabPageSize(TabPage tabPage, int width, int height)
        {
            // Set size for each page
            tabPage.Size = new Size(width, height);

            // Add the tab page and its size to the dictionary
            originalTabSizes.Add(tabPage, tabPage.Size);
        }
        private void ConfigTabs_SelectedIndexChanged(object sender, EventArgs e)
        {
            // Get the selected tab page
            TabPage selectedTabPage = ConfigTabs.SelectedTab;

            // Resize the form based on the size of the selected tab page
            this.Size = new Size(originalTabSizes[selectedTabPage].Width + this.Padding.Horizontal,
                                 originalTabSizes[selectedTabPage].Height + this.Padding.Vertical);
        }
        #endregion

        #region Configs

        private void LoadConfigs()
        {
            LoadChattingConfig();
            //LoadFrontConfig(); TODO
            LoadGameConfig();
            LoadGatewayConfig();
            LoadWorldConfig();
        }

        #region Chatting
        private void LoadChattingConfig()
        {
            try
            {
                // Read the JSON file contents
                string jsonFilePath = Path.Combine(Application.StartupPath, "Servers", "Chatting", "config.json");
                string jsonText = File.ReadAllText(jsonFilePath);

                // Parse the JSON string
                JObject jsonObject = JObject.Parse(jsonText);

                // Extract values and assign them to TextBoxes
                PublicIPBox.Text = jsonObject["chatting_server_info"]["public_ip"].ToString();
                PublicPortBox.Text = jsonObject["chatting_server_info"]["public_port"].ToString();
                WorldIPBox.Text = jsonObject["chatting_server_info"]["world_server_connect_ip"].ToString();
                WorldPortBox.Text = jsonObject["chatting_server_info"]["world_server_connect_port"].ToString();
                WorldSocketBox.Text = jsonObject["chatting_server_info"]["max_worldserver_connection_socket_count"].ToString();
                ClientSocketBox.Text = jsonObject["chatting_server_info"]["max_client_socket_count"].ToString();
                WorkerCountBox.Text = jsonObject["chatting_server_info"]["max_worker_count"].ToString();
                IOCPThreadBox.Text = jsonObject["chatting_server_info"]["iocp_thread_count"].ToString();
                RecBufferBox.Text = jsonObject["chatting_server_info"]["recv_buffer_size"].ToString();
                SendBufferBox.Text = jsonObject["chatting_server_info"]["send_buffer_size"].ToString();
                LogFileLevelBox.Text = jsonObject["chatting_server_info"]["log_file_level"].ToString();
                LogConsoleLevelBox.Text = jsonObject["chatting_server_info"]["log_console_level"].ToString();
                MakeDumpBox.Text = jsonObject["chatting_server_info"]["make_dump"].ToString();
                DumpSPathBox.Text = jsonObject["chatting_server_info"]["dump_server_path"].ToString();
                DumpPathBox.Text = jsonObject["chatting_server_info"]["dump_path"].ToString();
                AMsgBox.Text = jsonObject["chatting_server_info"]["assert_messagebox"].ToString();
            }
            catch (Exception ex)
            {
                // Handle any errors that may occur during reading or parsing the JSON file
                MessageBox.Show("Error loading config values: " + ex.Message, "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }
        #endregion

        #region Front

        #endregion

        #region Game
        private void LoadGameConfig()
        {
            try
            {
                // Read the JSON file contents
                string jsonFilePath = Path.Combine(Application.StartupPath, "Servers", "Game", "config.json");
                string jsonText = File.ReadAllText(jsonFilePath);

                // Parse the JSON string
                JObject jsonObject = JObject.Parse(jsonText);

                // Extract values and assign them to TextBoxes
                GAMEWorldIPBox.Text = jsonObject["game_server_info"]["world_server_connect_ip"].ToString();
                GAMEWorldPortBox.Text = jsonObject["game_server_info"]["world_server_connect_port"].ToString();
                GAMEGatewaySocketBox.Text = jsonObject["game_server_info"]["max_gateway_socket_count"].ToString();
                GAMEWorkerBox.Text = jsonObject["game_server_info"]["max_worker_count"].ToString();
                GAMEIOCPBox.Text = jsonObject["game_server_info"]["iocp_thread_count"].ToString();
                GAMERecBox.Text = jsonObject["game_server_info"]["recv_buffer_size"].ToString();
                GAMESendBox.Text = jsonObject["game_server_info"]["send_buffer_size"].ToString();
                GAMELogFileBox.Text = jsonObject["game_server_info"]["log_file_level"].ToString();
                GAMELogConsoleBox.Text = jsonObject["game_server_info"]["log_console_level"].ToString();
                GAMEMakeDumpBox.Text = jsonObject["game_server_info"]["make_dump"].ToString();
                GAMEDumpServerPathBox.Text = jsonObject["game_server_info"]["dump_server_path"].ToString();
                GAMEDumpPathBox.Text = jsonObject["game_server_info"]["dump_path"].ToString();
                GAMEAssertBox.Text = jsonObject["game_server_info"]["assert_messagebox"].ToString();
            }
            catch (Exception ex)
            {
                // Handle any errors that may occur during reading or parsing the JSON file
                MessageBox.Show("Error loading config values: " + ex.Message, "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }
        #endregion

        #region Gateway
        private void LoadGatewayConfig()
        {
            try
            {
                // Read the JSON file contents
                string jsonFilePath = Path.Combine(Application.StartupPath, "Servers", "Gateway", "config.json");
                string jsonText = File.ReadAllText(jsonFilePath);

                // Parse the JSON string
                JObject jsonObject = JObject.Parse(jsonText);

                // Extract values and assign them to TextBoxes
                GatewayIP.Text = jsonObject["gateway_server_info"]["public_ip"].ToString();
                GatewayPort.Text = jsonObject["gateway_server_info"]["public_port"].ToString();
                GatewayConnectIP.Text = jsonObject["gateway_server_info"]["world_server_connect_ip"].ToString();
                GatewayConnectPort.Text = jsonObject["gateway_server_info"]["world_server_connect_port"].ToString();
                GatewaySocket.Text = jsonObject["gateway_server_info"]["max_client_socket_count"].ToString();
                GatewayWorker.Text = jsonObject["gateway_server_info"]["max_worker_count"].ToString();
                GatewayIOCP.Text = jsonObject["gateway_server_info"]["iocp_thread_count"].ToString();
                GatewayRec.Text = jsonObject["gateway_server_info"]["recv_buffer_size"].ToString();
                GatewaySend.Text = jsonObject["gateway_server_info"]["send_buffer_size"].ToString();
                GatewayFileLevel.Text = jsonObject["gateway_server_info"]["log_file_level"].ToString();
                GatewayConsoleLevel.Text = jsonObject["gateway_server_info"]["log_console_level"].ToString();
                GatewayMakeDump.Text = jsonObject["gateway_server_info"]["make_dump"].ToString();
                GatewayServerPath.Text = jsonObject["gateway_server_info"]["dump_server_path"].ToString();
                GatewayPath.Text = jsonObject["gateway_server_info"]["dump_path"].ToString();
                GatewayAMessageBox.Text = jsonObject["gateway_server_info"]["assert_messagebox"].ToString();
            }
            catch (Exception ex)
            {
                // Handle any errors that may occur during reading or parsing the JSON file
                MessageBox.Show("Error loading config values: " + ex.Message, "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }
        #endregion

        #region World
        private void LoadWorldConfig()
        {
            try
            {
                // Read the JSON file contents
                string jsonFilePath = Path.Combine(Application.StartupPath, "Servers", "World", "config.json");
                string jsonText = File.ReadAllText(jsonFilePath);

                // Parse the JSON string
                JObject jsonObject = JObject.Parse(jsonText);

                // Extract values and assign them to TextBoxes
                WorldID.Text = jsonObject["world_server_info"]["server_id"].ToString();
                WorldPort.Text = jsonObject["world_server_info"]["game_server_connect_port"].ToString();
                WorldMaxCount.Text = jsonObject["world_server_info"]["max_game_server_socket_count"].ToString();
                WorldConnectPort.Text = jsonObject["world_server_info"]["gateway_server_connect_port"].ToString();
                WorldSocket.Text = jsonObject["world_server_info"]["max_gateway_server_socket_count"].ToString();
                WorldConnectPort1.Text = jsonObject["world_server_info"]["chatting_server_connect_port"].ToString();
                WorldWorker.Text = jsonObject["world_server_info"]["max_worker_count"].ToString();
                WorldIOCP.Text = jsonObject["world_server_info"]["iocp_thread_count"].ToString();
                WorldRec.Text = jsonObject["world_server_info"]["recv_buffer_size"].ToString();
                WorldSend.Text = jsonObject["world_server_info"]["send_buffer_size"].ToString();
                WorldFileLevel.Text = jsonObject["world_server_info"]["log_file_level"].ToString();
                WorldConsoleLevel.Text = jsonObject["world_server_info"]["log_console_level"].ToString();
                WorldDump.Text = jsonObject["world_server_info"]["make_dump"].ToString();
                WorldServerPath.Text = jsonObject["world_server_info"]["dump_server_path"].ToString();
                WorldDumpPath.Text = jsonObject["world_server_info"]["dump_path"].ToString();
                WorldAMessagebox.Text = jsonObject["world_server_info"]["assert_messagebox"].ToString();
                WorldIP.Text = jsonObject["front_db_info"]["ip"].ToString();
                WorldPort1.Text = jsonObject["front_db_info"]["port"].ToString();
                WorldUser.Text = jsonObject["front_db_info"]["user"].ToString();
                WorldPW.Text = jsonObject["front_db_info"]["pw"].ToString();
                WorldName.Text = jsonObject["front_db_info"]["db"].ToString();
            }
            catch (Exception ex)
            {
                // Handle any errors that may occur during reading or parsing the JSON file
                MessageBox.Show("Error loading config values: " + ex.Message, "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }
        #endregion

        #endregion
    }
}