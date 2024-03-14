using MySql.Data.MySqlClient;
using System;
using System.Collections.Generic;
using System.Data;
using System.Linq;
using System.Text;
using System.Threading.Tasks;

namespace Server_Console.Database
{
    internal class DatabaseFunctions
    {
        public static bool TestDatabaseConnection(string hostname, string username, string password)
        {
            string connectionString = $"server={hostname};user={username};password={password};";
            try
            {
                using (MySqlConnection conn = new MySqlConnection(connectionString))
                {
                    conn.Open();
                    return true;
                }
            }
            catch
            {
                return false;
            }
        }

        public static bool PrimaryKeyCheck(string connectionString, string tableName)
        {
            try
            {
                using (var connection = new MySqlConnection(connectionString))
                {
                    connection.Open();

                    DataTable schema = connection.GetSchema("Columns", [null, null, tableName]);

                    // Check if any column is marked as primary key
                    foreach (DataRow row in schema.Rows)
                    {
                        bool isPrimaryKey = Convert.ToBoolean(row["COLUMN_KEY"].ToString()?.Equals("pri", StringComparison.CurrentCultureIgnoreCase));
                        if (isPrimaryKey)
                        {
                            return true;
                        }
                    }
                    return false;
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show(ex.Message, "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                return false;
            }
        }

    }
}
