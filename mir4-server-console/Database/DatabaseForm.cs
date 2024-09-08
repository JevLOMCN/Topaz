using MySql.Data.MySqlClient;
using Server_Console.Properties;
using System;
using System.Collections.Generic;
using System.Data;
using System.Data.SqlClient;
using System.Diagnostics;
using System.Diagnostics.Eventing.Reader;
using System.IO;
using System.Text;
using System.Windows.Forms;

namespace Server_Console.Database
{
    public partial class DatabaseForm : Form
    {
        private Dictionary<TabPage, Size> originalTabSizes;
#nullable enable
        private List<string?> schemaNames = new List<string?>();
        public DatabaseForm()
        {
            InitializeComponent();
            SetTabSize();
        }

        #region Database Load
        private void DatabaseForm_Load(object sender, EventArgs e)
        {
            //Clear tsm_KeyWarning
            tsm_KeyWarning.Text = string.Empty;
            tsm_TableDescription.Text = string.Empty;

            //Check if the database is configured
            if (!Settings.Default.Configured)
            {
                //Show message box with ok/cancel buttons and a warning icon, if the result is ok, open the database configuration form
                DialogResult result = MessageBox.Show("Database is not configured. Please configure the database settings.", "Database Configuration", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                if (result == DialogResult.OK)
                {
                    DatabaseConfiguration dbConfig = new DatabaseConfiguration();
                    dbConfig.ShowDialog();
                }
                else
                {
                    //Close the form
                    this.Close();
                }
            }
            else
            {
                //Load the database data
                LoadAdminData();
                LoadDeviceData();
                LoadFrontData();
                LoadGameData();
                LoadUserData();
            }
        }
        #endregion

        #region Page Resize
        private void SetTabSize()
        {
            originalTabSizes = new Dictionary<TabPage, Size>();

            //Set the size for each Page
            SetTabPageSize(tabAdmin, 1116, 618);
            SetTabPageSize(tabDevice, 1116, 618);
            SetTabPageSize(tabFront, 1116, 618);
            SetTabPageSize(tabGame, 1116, 618);
            SetTabPageSize(tabUser, 1116, 618);
            SetTabPageSize(tabPlayerSearch, 1834, 1063);
        }
        private void SetTabPageSize(TabPage tabPage, int width, int height)
        {
            // Set size for each page
            tabPage.Size = new Size(width, height);

            // Add the tab page and its size to the dictionary
            originalTabSizes.Add(tabPage, tabPage.Size);
        }
        #endregion

        #region Main Load
        private void LoadDatabaseData(string databaseName, TreeView treeView, string tabName)
        {
            //clear the tree view
            treeView.Nodes.Clear();

            //Build the connections string from the settings and add the database name
            string connectionString = $"server={Settings.Default.Hostname};user={Settings.Default.Username};password={Settings.Default.Password};database={databaseName};";

            if (DatabaseTabs.SelectedTab != null && DatabaseTabs.SelectedTab.Text == tabName)
            {
                using (var connection = new MySqlConnection(connectionString))
                {
                    connection.Open();

                    // Get the schema information for tables
                    var schema = connection.GetSchema("Tables");

                    // Filter and display only table names
                    var tableNames = schema.AsEnumerable()
                                            .Select(row => row["TABLE_NAME"].ToString())
                                            .Where(tableName => !Properties.Settings.Default.IgnoreList.Contains(tableName));

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

        public static DataTable ExecuteQuery(string query, string databaseName)
        {
            DataTable resultTable = new DataTable();
            if (databaseName is null)
                return resultTable;

            try
            {
                string connectionString = DatabaseConfiguration.GetConnectionString(databaseName);
                using (var connection = new MySqlConnection(connectionString))
                {
                    connection.Open();
                    using (var command = new MySqlCommand(query, connection))
                    using (var adapter = new MySqlDataAdapter(command))
                    {
                        adapter.Fill(resultTable);
                    }
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show(ex.Message, "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
            return resultTable;
        }

        private void TreeView_NodeMouseClick(DataGridView dataGridView, string databaseName, TreeNodeMouseClickEventArgs e)
        {
            string tableName = e.Node.Text;

            string connectionString = $"server={Settings.Default.Hostname};user={Settings.Default.Username};password={Settings.Default.Password};database={databaseName};";
            string query = $"SELECT * FROM {tableName};";

            using (var connection = new MySqlConnection(connectionString))
            {
                connection.Open();
                MySqlCommand command = new MySqlCommand(query, connection);

                /* ** NOTE -- Check all timestamps in the database are set to a valid DATE/TIME and not 0000-00-00 00:00:00
                    Found So Far:
                    Datbase name: mm_game_db -- Table Names: achievement_clear_tb, character_tb, quest_daily_list_tb, quest_weekly_list_tb
                 */

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

                connection.Close();
            }

            if (!DatabaseFunctions.PrimaryKeyCheck(connectionString, tableName))
            {
                dataGridView.ReadOnly = true;
                tsm_KeyWarning.ForeColor = System.Drawing.Color.Red;
                tsm_KeyWarning.Text = "Table does not have a primary key. Data cannot be edited.";
            }
            else
            {
                tsm_KeyWarning.ForeColor = System.Drawing.Color.Green;
                tsm_KeyWarning.Text = "Table can be edited";
                dataGridView.ReadOnly = false;
            }
            #endregion

            #region Table Descriptions
            if (Settings.Default.TableDescription.Count == Settings.Default.TableDescriptionCount)
            {
                if (e.Node != null && e.Node.Text == "_spschema_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[1].ToString();
                }
                else if (e.Node != null && e.Node.Text == "_tableschema_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[2].ToString();
                }
                else if (e.Node != null && e.Node.Text == "errortype_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[3].ToString();
                }
                else if (e.Node != null && e.Node.Text == "gamedata_achievement_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[4].ToString();
                }
                else if (e.Node != null && e.Node.Text == "gamedata_costume_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[5].ToString();
                }
                else if (e.Node != null && e.Node.Text == "gamedata_item_option_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[6].ToString();
                }
                else if (e.Node != null && e.Node.Text == "gamedata_item_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[7].ToString();
                }
                else if (e.Node != null && e.Node.Text == "gamedata_quest_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[8].ToString();
                }
                else if (e.Node != null && e.Node.Text == "gamedata_skill_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[9].ToString();
                }
                else if (e.Node != null && e.Node.Text == "gamedata_stage_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[10].ToString();
                }
                else if (e.Node != null && e.Node.Text == "spschema_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[11].ToString();
                }
                else if (e.Node != null && e.Node.Text == "tableschema_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[12].ToString();
                }
                else if (e.Node != null && e.Node.Text == "department_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[13].ToString();
                }
                else if (e.Node != null && e.Node.Text == "device_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[14].ToString();
                }
                else if (e.Node != null && e.Node.Text == "os_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[15].ToString();
                }
                else if (e.Node != null && e.Node.Text == "account_block_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[16].ToString();
                }
                else if (e.Node != null && e.Node.Text == "account_grade_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[17].ToString();
                }
                else if (e.Node != null && e.Node.Text == "account_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[18].ToString();
                }
                else if (e.Node != null && e.Node.Text == "db_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[19].ToString();
                }
                else if (e.Node != null && e.Node.Text == "db_type_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[20].ToString();
                }
                else if (e.Node != null && e.Node.Text == "db_user_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[21].ToString();
                }
                else if (e.Node != null && e.Node.Text == "nation_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[22].ToString();
                }
                else if (e.Node != null && e.Node.Text == "patch_url_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[23].ToString();
                }
                else if (e.Node != null && e.Node.Text == "revision_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[24].ToString();
                }
                else if (e.Node != null && e.Node.Text == "sdk_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[25].ToString();
                }
                else if (e.Node != null && e.Node.Text == "server_db_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[26].ToString();
                }
                else if (e.Node != null && e.Node.Text == "server_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[27].ToString();
                }
                else if (e.Node != null && e.Node.Text == "server_type_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[28].ToString();
                }
                else if (e.Node != null && e.Node.Text == "world_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[29].ToString();
                }
                else if (e.Node != null && e.Node.Text == "_blackiron_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[30].ToString();
                }
                else if (e.Node != null && e.Node.Text == "_reward_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[31].ToString();
                }
                else if (e.Node != null && e.Node.Text == "account_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[32].ToString();
                }
                else if (e.Node != null && e.Node.Text == "achievement_clear_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[33].ToString();
                }
                else if (e.Node != null && e.Node.Text == "achievement_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[34].ToString();
                }
                else if (e.Node != null && e.Node.Text == "character_delete_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[35].ToString();
                }
                else if (e.Node != null && e.Node.Text == "character_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[36].ToString();
                }
                else if (e.Node != null && e.Node.Text == "costume_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[37].ToString();
                }
                else if (e.Node != null && e.Node.Text == "equip_item_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[38].ToString();
                }
                else if (e.Node != null && e.Node.Text == "equip_jewel_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[39].ToString();
                }
                else if (e.Node != null && e.Node.Text == "equip_mastery_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[40].ToString();
                }
                else if (e.Node != null && e.Node.Text == "item_character_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[41].ToString();
                }
                else if (e.Node != null && e.Node.Text == "item_delete_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[42].ToString();
                }
                else if (e.Node != null && e.Node.Text == "item_option_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[43].ToString();
                }
                else if (e.Node != null && e.Node.Text == "item_reinforce_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[44].ToString();
                }
                else if (e.Node != null && e.Node.Text == "item_smelting_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[45].ToString();
                }
                else if (e.Node != null && e.Node.Text == "item_stack_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[46].ToString();
                }
                else if (e.Node != null && e.Node.Text == "item_tagbuff_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[47].ToString();
                }
                else if (e.Node != null && e.Node.Text == "item_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[48].ToString();
                }
                else if (e.Node != null && e.Node.Text == "item_transcendence_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[49].ToString();
                }
                else if (e.Node != null && e.Node.Text == "mail_itemidx_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[50].ToString();
                }
                else if (e.Node != null && e.Node.Text == "mail_itemuid_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[51].ToString();
                }
                else if (e.Node != null && e.Node.Text == "mail_money_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[52].ToString();
                }
                else if (e.Node != null && e.Node.Text == "mail_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[53].ToString();
                }
                else if (e.Node != null && e.Node.Text == "pulse_blood_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[54].ToString();
                }
                else if (e.Node != null && e.Node.Text == "pulse_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[55].ToString();
                }
                else if (e.Node != null && e.Node.Text == "quest_daily_list_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[56].ToString();
                }
                else if (e.Node != null && e.Node.Text == "quest_daily_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[57].ToString();
                }
                else if (e.Node != null && e.Node.Text == "quest_main_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[58].ToString();
                }
                else if (e.Node != null && e.Node.Text == "quest_sub_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[59].ToString();
                }
                else if (e.Node != null && e.Node.Text == "quest_weekly_list_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[60].ToString();
                }
                else if (e.Node != null && e.Node.Text == "quest_weekly_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[61].ToString();
                }
                else if (e.Node != null && e.Node.Text == "skill_active_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[62].ToString();
                }
                else if (e.Node != null && e.Node.Text == "skill_passive_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[63].ToString();
                }
                else if (e.Node != null && e.Node.Text == "treasure_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[64].ToString();
                }
                else if (e.Node != null && e.Node.Text == "tutorial_clear_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[65].ToString();
                }
                else if (e.Node != null && e.Node.Text == "uid_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[66].ToString();
                }
                else if (e.Node != null && e.Node.Text == "vehicle_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[67].ToString();
                }
                else if (e.Node != null && e.Node.Text == "waypoint_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[68].ToString();
                }
                else if (e.Node != null && e.Node.Text == "user_tb")
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[69].ToString();
                }
                else
                {
                    tsm_TableDescription.Text = Settings.Default.TableDescription[0].ToString();
                }
            }
            else
            {
                MessageBox.Show("No Table Description can be found!", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
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
            TreeView_NodeMouseClick(AdminData, "admin_db", e);
        }

        private void AdminTree_AfterSelect(object sender, TreeNodeMouseClickEventArgs e)
        {
            TreeView_NodeMouseClick(AdminData, "admin_db", e);
        }
        #endregion

        #region Device
        private void LoadDeviceData()
        {
            LoadDatabaseData("mm_device_db", DeviceTree, "Device");
        }
        private void DeviceTree_NodeMouseClick(object sender, TreeNodeMouseClickEventArgs e)
        {
            TreeView_NodeMouseClick(DeviceData, "mm_device_db", e);
        }
        private void DeviceTree_AfterSelect(object sender, TreeViewEventArgs e)
        {
            LoadDatabaseData("mm_device_db", DeviceTree, "Device");
        }

        #endregion

        #region Front
        private void LoadFrontData()
        {
            LoadDatabaseData("mm_front_db", FrontTree, "Front");
        }
        private void FrontTree_NodeMouseClick(object sender, TreeNodeMouseClickEventArgs e)
        {
            TreeView_NodeMouseClick(FrontData, "mm_front_db", e);
        }
        #endregion

        #region Game
        private void LoadGameData()
        {
            LoadDatabaseData("mm_game_db", GameTree, "Game");
        }
        private void GameTree_NodeMouseClick(object sender, TreeNodeMouseClickEventArgs e)
        {
            TreeView_NodeMouseClick(GameData, "mm_game_db", e);
        }
        #endregion

        #region User
        private void LoadUserData()
        {
            LoadDatabaseData("mm_user_db", UserTree, "User");
        }
        private void UserTree_NodeMouseClick(object sender, TreeNodeMouseClickEventArgs e)
        {
            TreeView_NodeMouseClick(UserData, "mm_user_db", e);
        }
        #endregion

        #region DB Tabs Changed Index
        private void DatabaseTabs_SelectedIndexChanged(object sender, EventArgs e)
        {
            LoadAdminData();
            LoadDeviceData();
            LoadFrontData();
            LoadGameData();
            LoadUserData();


            // Get the selected tab page
            TabPage selectedTabPage = DatabaseTabs.SelectedTab;

            // Resize the form based on the size of the selected tab page
            this.Size = new Size(originalTabSizes[selectedTabPage].Width + this.Padding.Horizontal,
                                 originalTabSizes[selectedTabPage].Height + this.Padding.Vertical);

        }
        #endregion

        #region Update Data

        private void AdminData_CellEndEdit(object sender, DataGridViewCellEventArgs e)
        {
            DataGridView dgv = (DataGridView)sender;
            string tableName = AdminTree.SelectedNode.Text;

            // Ensure that we are dealing with data cells (not header cells or anything else)
            if (e.RowIndex >= 0 && e.ColumnIndex >= 0)
            {
                // Build the connection string
                string connectionString = $"server={Settings.Default.Hostname};user={Settings.Default.Username};password={Settings.Default.Password};database=admin_db;";

                // Get the column name and new value
                string columnName = dgv.Columns[e.ColumnIndex].Name;
                object newValue = dgv.Rows[e.RowIndex].Cells[e.ColumnIndex].Value;

                // Get the unique column name and value
                string uniqueColumnName = dgv.Columns[0].Name;
                object uniqueColumnValue = dgv.Rows[e.RowIndex].Cells[uniqueColumnName].Value;

                // Update the data
                using (var connection = new MySqlConnection(connectionString))
                {
                    connection.Open();

                    string query = $"UPDATE {tableName} SET {columnName} = @newValue WHERE {uniqueColumnName} = @uniqueColumnValue";

                    using (var command = new MySqlCommand(query, connection))
                    {
                        command.Parameters.AddWithValue("@newValue", newValue);
                        command.Parameters.AddWithValue("@uniqueColumnValue", uniqueColumnValue);

                        try
                        {
                            int rowsAffected = command.ExecuteNonQuery();
                            if (rowsAffected > 0)
                            {
                                tsm_KeyWarning.Text = tsm_KeyWarning.Text + " | Data updated successfully!";
                                tsm_KeyWarning.ForeColor = System.Drawing.Color.Green;
                            }
                            else
                            {
                                tsm_KeyWarning.Text = tsm_KeyWarning.Text + " | No rows were updated!";
                                tsm_KeyWarning.ForeColor = System.Drawing.Color.Red;
                            }
                        }
                        catch (Exception ex)
                        {
                            MessageBox.Show($"Error: {ex.Message}", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                        }
                    }
                }
            }

        }

        private void DeviceData_CellEndEdit(object sender, DataGridViewCellEventArgs e)
        {
            DataGridView dgv = (DataGridView)sender;
            string tableName = DeviceTree.SelectedNode.Text;

            // Ensure that we are dealing with data cells (not header cells or anything else)
            if (e.RowIndex >= 0 && e.ColumnIndex >= 0)
            {
                // Build the connection string
                string connectionString = $"server={Settings.Default.Hostname};user={Settings.Default.Username};password={Settings.Default.Password};database=mm_device_db;";

                // Get the column name and new value
                string columnName = dgv.Columns[e.ColumnIndex].Name;
                object newValue = dgv.Rows[e.RowIndex].Cells[e.ColumnIndex].Value;

                // Get the unique column name and value
                string uniqueColumnName = string.Empty;

                //Define the unique column name based on the table name
                if (tableName == "deviceid_tb")
                {
                    uniqueColumnName = dgv.Columns[1].Name;
                }
                else
                {
                    uniqueColumnName = dgv.Columns[0].Name;
                }

                object uniqueColumnValue = dgv.Rows[e.RowIndex].Cells[uniqueColumnName].Value;

                // Update the data
                using (var connection = new MySqlConnection(connectionString))
                {
                    connection.Open();

                    string query = $"UPDATE {tableName} SET {columnName} = @newValue WHERE {uniqueColumnName} = @uniqueColumnValue";

                    using (var command = new MySqlCommand(query, connection))
                    {
                        command.Parameters.AddWithValue("@newValue", newValue);
                        command.Parameters.AddWithValue("@uniqueColumnValue", uniqueColumnValue);

                        try
                        {
                            int rowsAffected = command.ExecuteNonQuery();
                            if (rowsAffected > 0)
                            {
                                tsm_KeyWarning.Text = tsm_KeyWarning.Text + " | Data updated successfully!";
                                tsm_KeyWarning.ForeColor = System.Drawing.Color.Green;
                            }
                            else
                            {
                                tsm_KeyWarning.Text = tsm_KeyWarning.Text + " | No rows were updated!";
                                tsm_KeyWarning.ForeColor = System.Drawing.Color.Red;
                            }
                        }
                        catch (Exception ex)
                        {
                            MessageBox.Show($"Error: {ex.Message}", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                        }
                    }
                }
            }
        }

        private void FrontData_CellEndEdit(object sender, DataGridViewCellEventArgs e)
        {
            DataGridView dgv = (DataGridView)sender;
            string tableName = FrontTree.SelectedNode.Text;

            // Ensure that we are dealing with data cells (not header cells or anything else)
            if (e.RowIndex >= 0 && e.ColumnIndex >= 0)
            {
                // Build the connection string
                string connectionString = $"server={Settings.Default.Hostname};user={Settings.Default.Username};password={Settings.Default.Password};database=mm_front_db;";

                // Get the column name and new value
                string columnName = dgv.Columns[e.ColumnIndex].Name;
                object newValue = dgv.Rows[e.RowIndex].Cells[e.ColumnIndex].Value;

                // Get the unique column name and value
                string uniqueColumnName = dgv.Columns[0].Name;
                object uniqueColumnValue = dgv.Rows[e.RowIndex].Cells[uniqueColumnName].Value;

                // Update the data
                using (var connection = new MySqlConnection(connectionString))
                {
                    connection.Open();

                    string query = $"UPDATE {tableName} SET {columnName} = @newValue WHERE {uniqueColumnName} = @uniqueColumnValue";

                    using (var command = new MySqlCommand(query, connection))
                    {
                        command.Parameters.AddWithValue("@newValue", newValue);
                        command.Parameters.AddWithValue("@uniqueColumnValue", uniqueColumnValue);

                        try
                        {
                            int rowsAffected = command.ExecuteNonQuery();
                            if (rowsAffected > 0)
                            {
                                tsm_KeyWarning.Text = tsm_KeyWarning.Text + " | Data updated successfully!";
                                tsm_KeyWarning.ForeColor = System.Drawing.Color.Green;
                            }
                            else
                            {
                                tsm_KeyWarning.Text = tsm_KeyWarning.Text + " | No rows were updated!";
                                tsm_KeyWarning.ForeColor = System.Drawing.Color.Red;
                            }
                        }
                        catch (Exception ex)
                        {
                            MessageBox.Show($"Error: {ex.Message}", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                        }
                    }
                }
            }
        }

        private void GameData_CellEndEdit(object sender, DataGridViewCellEventArgs e)
        {
            DataGridView dgv = (DataGridView)sender;
            string tableName = GameTree.SelectedNode.Text;

            // Ensure that we are dealing with data cells (not header cells or anything else)
            if (e.RowIndex >= 0 && e.ColumnIndex >= 0)
            {
                // Build the connection string
                string connectionString = $"server={Settings.Default.Hostname};user={Settings.Default.Username};password={Settings.Default.Password};database=mm_game_db;";

                // Get the column name and new value
                string columnName = dgv.Columns[e.ColumnIndex].Name;
                object newValue = dgv.Rows[e.RowIndex].Cells[e.ColumnIndex].Value;

                // Get the unique column name and value
                string uniqueColumnName = string.Empty;
                if (tableName == "equip_item_tb")
                {
                    uniqueColumnName = dgv.Columns[2].Name;
                }
                else if (tableName == "uid_tb")
                {
                    uniqueColumnName = dgv.Columns[1].Name;
                }
                else
                {
                    uniqueColumnName = dgv.Columns[0].Name;
                }

                object uniqueColumnValue = dgv.Rows[e.RowIndex].Cells[uniqueColumnName].Value;

                // Update the data
                using (var connection = new MySqlConnection(connectionString))
                {
                    connection.Open();

                    string query = $"UPDATE {tableName} SET {columnName} = @newValue WHERE {uniqueColumnName} = @uniqueColumnValue";

                    using (var command = new MySqlCommand(query, connection))
                    {
                        command.Parameters.AddWithValue("@newValue", newValue);
                        command.Parameters.AddWithValue("@uniqueColumnValue", uniqueColumnValue);

                        try
                        {
                            int rowsAffected = command.ExecuteNonQuery();
                            if (rowsAffected > 0)
                            {
                                tsm_KeyWarning.Text = tsm_KeyWarning.Text + $" | {rowsAffected.ToString()} Rows updated successfully!";
                                tsm_KeyWarning.ForeColor = System.Drawing.Color.Green;
                            }
                            else
                            {
                                tsm_KeyWarning.Text = tsm_KeyWarning.Text + " | No rows were updated!";
                                tsm_KeyWarning.ForeColor = System.Drawing.Color.Red;
                            }
                        }
                        catch (Exception ex)
                        {
                            MessageBox.Show($"Error: {ex.Message}", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                        }
                    }
                }
            }
        }

        private void UserData_CellEndEdit(object sender, DataGridViewCellEventArgs e)
        {
            DataGridView dgv = (DataGridView)sender;
            string tableName = UserTree.SelectedNode.Text;

            // Ensure that we are dealing with data cells (not header cells or anything else)
            if (e.RowIndex >= 0 && e.ColumnIndex >= 0)
            {
                // Build the connection string
                string connectionString = $"server={Settings.Default.Hostname};user={Settings.Default.Username};password={Settings.Default.Password};database=mm_user_db;";

                // Get the column name and new value
                string columnName = dgv.Columns[e.ColumnIndex].Name;
                object newValue = dgv.Rows[e.RowIndex].Cells[e.ColumnIndex].Value;

                // Get the unique column name and value
                string uniqueColumnName = dgv.Columns[0].Name;
                object uniqueColumnValue = dgv.Rows[e.RowIndex].Cells[uniqueColumnName].Value;

                // Update the data
                using (var connection = new MySqlConnection(connectionString))
                {
                    connection.Open();

                    string query = $"UPDATE {tableName} SET {columnName} = @newValue WHERE {uniqueColumnName} = @uniqueColumnValue";

                    using (var command = new MySqlCommand(query, connection))
                    {
                        command.Parameters.AddWithValue("@newValue", newValue);
                        command.Parameters.AddWithValue("@uniqueColumnValue", uniqueColumnValue);

                        try
                        {
                            int rowsAffected = command.ExecuteNonQuery();
                            if (rowsAffected > 0)
                            {
                                tsm_KeyWarning.Text = tsm_KeyWarning.Text + " | Data updated successfully!";
                                tsm_KeyWarning.ForeColor = System.Drawing.Color.Green;
                            }
                            else
                            {
                                tsm_KeyWarning.Text = tsm_KeyWarning.Text + " | No rows were updated!";
                                tsm_KeyWarning.ForeColor = System.Drawing.Color.Red;
                            }
                        }
                        catch (Exception ex)
                        {
                            MessageBox.Show($"Error: {ex.Message}", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                        }
                    }
                }
            }
        }

        private void AdminData_CellLeave(object sender, DataGridViewCellEventArgs e)
        {
            tsm_KeyWarning.Text = "Table can be edited";
            tsm_KeyWarning.ForeColor = System.Drawing.Color.Green;
        }

        private void DeviceData_CellLeave(object sender, DataGridViewCellEventArgs e)
        {
            tsm_KeyWarning.Text = "Table can be edited";
            tsm_KeyWarning.ForeColor = System.Drawing.Color.Green;
        }

        private void FrontData_CellLeave(object sender, DataGridViewCellEventArgs e)
        {
            tsm_KeyWarning.Text = "Table can be edited";
            tsm_KeyWarning.ForeColor = System.Drawing.Color.Green;
        }

        private void GameData_CellLeave(object sender, DataGridViewCellEventArgs e)
        {
            tsm_KeyWarning.Text = "Table can be edited";
            tsm_KeyWarning.ForeColor = System.Drawing.Color.Green;
        }

        private void UserData_CellLeave(object sender, DataGridViewCellEventArgs e)
        {
            tsm_KeyWarning.Text = "Table can be edited";
            tsm_KeyWarning.ForeColor = System.Drawing.Color.Green;
        }
        #endregion

        #region ToolStripMenu
        private void tsmDB_Settings_Click(object sender, EventArgs e)
        {
            //load the database configuration form
            DatabaseConfiguration dbConfig = new DatabaseConfiguration();
            dbConfig.ShowDialog();
        }
        #endregion

        #region Table Search //Only admin tree for now. May need to branch the search function to use selected index changed.
        private void TableSearchButton_Click(object sender, EventArgs e)
        {
        //    string searchText = TableSearchBox.Text.Trim();

        //    if (!string.IsNullOrEmpty(searchText))
        //    {
        //        SearchInAdminTree(searchText);
        //    }
        //    else
        //    {
        //        MessageBox.Show("Please add a search term.", "Empty Search," , MessageBoxButtons.OK, MessageBoxIcon.Information);
        //    }
        }
        //private void SearchInAdminTree(string searchText)
        //{
        //    TreeNode node = FindNodeByText(AdminTree.Nodes, searchText);

        //    if (node != null)
        //    {
        //        AdminTree.SelectedNode = node;
        //        AdminTree.Focus();
        //        MessageBox.Show($"Found '{searchText}' in AdminTree.", "Search Result", MessageBoxButtons.OK, MessageBoxIcon.Information);
        //    }
        //    else
        //    {
        //        MessageBox.Show($"'{searchText}' not found in AdminTree.", "Search Result", MessageBoxButtons.OK, MessageBoxIcon.Information);
        //    }
        //}

        //private TreeNode FindNodeByText(TreeNodeCollection nodes, string searchText)
        //{
        //    foreach (TreeNode node in nodes)
        //    {
        //        if (node.Text.Equals(searchText, StringComparison.OrdinalIgnoreCase))
        //        {
        //            return node;
        //        }

        //        TreeNode foundNode = FindNodeByText(node.Nodes, searchText);
        //        if (foundNode != null)
        //        {
        //            return foundNode;
        //        }
        //    }
        //        return null;
        //    }
        //}
        #endregion
    }
}