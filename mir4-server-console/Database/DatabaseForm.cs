using MySql.Data.MySqlClient;
using System;
using System.Collections.Generic;
using System.Data;
using System.Data.SqlClient;
using System.Diagnostics;
using System.IO;
using System.Text;
using System.Windows.Forms;

namespace Server_Console.Database
{
    public partial class DatabaseForm : Form
    {
        private List<string> schemaNames = new List<string>();

        public DatabaseForm()
        {
            InitializeComponent();
        }
        private void DatabaseForm_Load(object sender, EventArgs e)
        {
            LoadAdminData();
            LoadDeviceData();
            LoadFrontData();
            LoadGameData();
            LoadUserData();
        }

        #region Main Load
        private void LoadDatabaseData(string databaseName, TreeView treeView, string tabName)
        {
            if (DatabaseTabs.SelectedTab.Text == tabName)
            {
                string connectionString = $"server=192.168.1.27;user=dev;password=1111;database={databaseName};";

                using (var connection = new MySqlConnection(connectionString))
                {
                    connection.Open();

                    // Get the schema information for tables
                    var schema = connection.GetSchema("Tables");

                    // Filter and display only table names
                    var tableNames = schema.AsEnumerable()
                                            .Select(row => row["TABLE_NAME"].ToString())
                                            .ToList();

                    // Display the list of table names
                    Debug.WriteLine($"Tables in the {databaseName} database:");
                    foreach (var tableName in tableNames)
                    {
                        Debug.WriteLine(tableName);
                        treeView.Nodes.Add(tableName);
                    }
                }
            }
        }

        private void TreeView_NodeMouseClick(TreeView treeView, DataGridView dataGridView, string databaseName, TreeNodeMouseClickEventArgs e)
        {
            string tableName = e.Node.Text;
            string connectionString = $"server=192.168.1.27;user=dev;password=1111;database={databaseName};";

            string query = $"SELECT * FROM {tableName}";

            using (var connection = new MySqlConnection(connectionString))
            {
                connection.Open();
                MySqlCommand command = new MySqlCommand(query, connection);

                try
                {
                    MySqlDataReader reader = command.ExecuteReader();
                    DataTable dataTable = new DataTable();
                    dataTable.Load(reader);
                    reader.Close();
                    dataGridView.DataSource = dataTable;
                }
                catch (Exception ex)
                {
                    MessageBox.Show($"Error: {ex.Message}", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                }
            }
        }
        #endregion

        #region Admin
        private void LoadAdminData()
        {
            LoadDatabaseData("admin_db", AdminTree, "Admin");
        }
        private void AdminTree_NodeMouseClick(object sender, TreeNodeMouseClickEventArgs e)
        {
            TreeView_NodeMouseClick(AdminTree, AdminData, "admin_db", e);
        }
        #endregion

        #region Device
        private void LoadDeviceData()
        {
            LoadDatabaseData("mm_device_db", DeviceTree, "Device");
        }
        private void DeviceTree_NodeMouseClick(object sender, TreeNodeMouseClickEventArgs e)
        {
            TreeView_NodeMouseClick(DeviceTree, DeviceData, "mm_device_db", e);
        }

        #endregion

        #region Front
        private void LoadFrontData()
        {
            LoadDatabaseData("mm_front_db", FrontTree, "Front");
        }
        private void FrontTree_NodeMouseClick(object sender, TreeNodeMouseClickEventArgs e)
        {
            TreeView_NodeMouseClick(FrontTree, FrontData, "mm_front_db", e);
        }
        #endregion

        #region Game
        private void LoadGameData()
        {
            LoadDatabaseData("mm_game_db", GameTree, "Game");
        }
        private void GameTree_NodeMouseClick(object sender, TreeNodeMouseClickEventArgs e)
        {
            TreeView_NodeMouseClick(GameTree, GameData, "mm_game_db", e);
        }
        #endregion

        #region User
        private void LoadUserData()
        {
            LoadDatabaseData("mm_user_db", UserTree, "User");
        }
        private void UserTree_NodeMouseClick(object sender, TreeNodeMouseClickEventArgs e)
        {
            TreeView_NodeMouseClick(UserTree, UserData, "mm_user_db", e);
        }
        #endregion

        private void DatabaseTabs_SelectedIndexChanged(object sender, EventArgs e)
        {
            LoadAdminData();
            LoadDeviceData();
            LoadFrontData();
            LoadGameData();
            LoadUserData();
        }
    }
}