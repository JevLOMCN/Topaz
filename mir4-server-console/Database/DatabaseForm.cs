using MySql.Data.MySqlClient;
using Server_Console.Properties;
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
#nullable enable
        private List<string?> schemaNames = new List<string?>();
        public DatabaseForm()
        {
            InitializeComponent();
        }
        private void DatabaseForm_Load(object sender, EventArgs e)
        {
            //Clear lbl_PrimaryKeyWarning
            lbl_PrimaryKeyWarning.Text = string.Empty;

            //Check if the database is configured
            if (!Settings.Default.Configured)
            {
                //show message box with ok/cancel buttons and a warning icon, if the result is ok, open the database configuration form
                DialogResult result = MessageBox.Show("Database is not configured. Please configure the database settings.", "Database Configuration", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                if (result == DialogResult.OK)
                {
                    DatabaseConfiguration dbConfig = new DatabaseConfiguration();
                    dbConfig.ShowDialog();
                }
                else
                {
                    //close the form
                    this.Close();
                }
            }
            else
            {
                //load the database data
                LoadAdminData();
                LoadDeviceData();
                LoadFrontData();
                LoadGameData();
                LoadUserData();
            }
        }

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
                lbl_PrimaryKeyWarning.ForeColor = System.Drawing.Color.Red;
                lbl_PrimaryKeyWarning.Text = "Table does not have a primary key. Data cannot be edited.";
            }
            else
            {
                lbl_PrimaryKeyWarning.ForeColor = System.Drawing.Color.Green;
                lbl_PrimaryKeyWarning.Text = "Table can be edited";
                dataGridView.ReadOnly = false;
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

        private void DatabaseTabs_SelectedIndexChanged(object sender, EventArgs e)
        {
            LoadAdminData();
            LoadDeviceData();
            LoadFrontData();
            LoadGameData();
            LoadUserData();
        }

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
                                lbl_PrimaryKeyWarning.Text = lbl_PrimaryKeyWarning.Text + " | Data updated successfully!";
                                lbl_PrimaryKeyWarning.ForeColor = System.Drawing.Color.Green;
                            }
                            else
                            {
                                lbl_PrimaryKeyWarning.Text = lbl_PrimaryKeyWarning.Text + " | No rows were updated!";
                                lbl_PrimaryKeyWarning.ForeColor = System.Drawing.Color.Red;
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
                                lbl_PrimaryKeyWarning.Text = lbl_PrimaryKeyWarning.Text + " | Data updated successfully!";
                                lbl_PrimaryKeyWarning.ForeColor = System.Drawing.Color.Green;
                            }
                            else
                            {
                                lbl_PrimaryKeyWarning.Text = lbl_PrimaryKeyWarning.Text + " | No rows were updated!";
                                lbl_PrimaryKeyWarning.ForeColor = System.Drawing.Color.Red;
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
                                lbl_PrimaryKeyWarning.Text = lbl_PrimaryKeyWarning.Text + " | Data updated successfully!";
                                lbl_PrimaryKeyWarning.ForeColor = System.Drawing.Color.Green;
                            }
                            else
                            {
                                lbl_PrimaryKeyWarning.Text = lbl_PrimaryKeyWarning.Text + " | No rows were updated!";
                                lbl_PrimaryKeyWarning.ForeColor = System.Drawing.Color.Red;
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
                                lbl_PrimaryKeyWarning.Text = lbl_PrimaryKeyWarning.Text + $" | {rowsAffected.ToString()} Rows updated successfully!";
                                lbl_PrimaryKeyWarning.ForeColor = System.Drawing.Color.Green;
                            }
                            else
                            {
                                lbl_PrimaryKeyWarning.Text = lbl_PrimaryKeyWarning.Text + " | No rows were updated!";
                                lbl_PrimaryKeyWarning.ForeColor = System.Drawing.Color.Red;
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
                                lbl_PrimaryKeyWarning.Text = lbl_PrimaryKeyWarning.Text + " | Data updated successfully!";
                                lbl_PrimaryKeyWarning.ForeColor = System.Drawing.Color.Green;
                            }
                            else
                            {
                                lbl_PrimaryKeyWarning.Text = lbl_PrimaryKeyWarning.Text + " | No rows were updated!";
                                lbl_PrimaryKeyWarning.ForeColor = System.Drawing.Color.Red;
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
            lbl_PrimaryKeyWarning.Text = "Table can be edited";
            lbl_PrimaryKeyWarning.ForeColor = System.Drawing.Color.Green;
        }

        private void DeviceData_CellLeave(object sender, DataGridViewCellEventArgs e)
        {
            lbl_PrimaryKeyWarning.Text = "Table can be edited";
            lbl_PrimaryKeyWarning.ForeColor = System.Drawing.Color.Green;
        }

        private void FrontData_CellLeave(object sender, DataGridViewCellEventArgs e)
        {
            lbl_PrimaryKeyWarning.Text = "Table can be edited";
            lbl_PrimaryKeyWarning.ForeColor = System.Drawing.Color.Green;
        }

        private void GameData_CellLeave(object sender, DataGridViewCellEventArgs e)
        {
            lbl_PrimaryKeyWarning.Text = "Table can be edited";
            lbl_PrimaryKeyWarning.ForeColor = System.Drawing.Color.Green;
        }

        private void UserData_CellLeave(object sender, DataGridViewCellEventArgs e)
        {
            lbl_PrimaryKeyWarning.Text = "Table can be edited";
            lbl_PrimaryKeyWarning.ForeColor = System.Drawing.Color.Green;
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

    }
}