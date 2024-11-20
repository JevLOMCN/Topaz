using Newtonsoft.Json;
using Newtonsoft.Json.Linq;
using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Data;
using System.Drawing;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using System.Windows.Forms;

namespace Server_Console.JSON
{
    public partial class RespawnForm : Form
    {
        private List<Respawn> respawnData;
        private Respawn selectedRespawn;
        private List<string> allJsonFiles = new List<string>();
        public RespawnForm()
        {
            InitializeComponent();
            LoadRespawnData();
        }

        #region Load Respawn Data
        public void LoadRespawnData()
        {
            string directoryPath = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "Servers", "Data", "Gen");

            RespawnData.Items.Clear();
            allJsonFiles.Clear();

            if (!Directory.Exists(directoryPath))
            {
                MessageBox.Show($"Directory not found: {directoryPath}", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                return;
            }

            string[] jsonFiles = Directory.GetFiles(directoryPath, "*.json");

            int totalMonsterCount = 0;

            foreach (string filePath in jsonFiles)
            {
                try
                {
                    string fileName = Path.GetFileName(filePath);
                    var item = new ListViewItem(fileName);
                    allJsonFiles.Add(fileName);
                    RespawnData.Items.Add(item);

                    // Read and parse the JSON file
                    string jsonContent = File.ReadAllText(filePath);

                    if (!string.IsNullOrWhiteSpace(jsonContent))
                    {
                        var jsonArray = JArray.Parse(jsonContent);

                        foreach (var entry in jsonArray)
                        {
                            if (entry["SpawnCount"] != null && int.TryParse(entry["SpawnCount"].ToString(), out int spawnCount))
                            {
                                totalMonsterCount += spawnCount; // Add SpawnCount to the total
                            }
                        }
                    }
                }
                catch (Exception ex)
                {

                }
            }

            // Update labels
            FileCountLabel.Text = $"Gen File Count: {allJsonFiles.Count}";
            MonsterCountLabel.Text = $"Monsters Count: {totalMonsterCount}";
        }
        #endregion

        #region Respawn Class
        public class Respawn
        {
            public int PosID { get; set; }
            public long Stage { get; set; }
            public long GenObjectID { get; set; }
            public int GenObjectType { get; set; }
            public int SpawnCount { get; set; }
            public int SpawnRange { get; set; }
            public int ActiveType { get; set; }
            public int RegMinTime { get; set; }
            public int RegMaxTime { get; set; }
            public double LocationX { get; set; }
            public double LocationY { get; set; }
            public double LocationZ { get; set; }
            public int RotationYaw { get; set; }
            public int LinkGroup { get; set; }
            public int LinkGroupDistance { get; set; }
            public int GenType { get; set; }
            public int FirstGenDelayMin { get; set; }
            public int FirstGenDelayMax { get; set; }
            public int GroupGenID { get; set; }
            public int GroupGenCountThreshold { get; set; }
            public int RoamingType { get; set; }
            public float ScaleX { get; set; }
            public float ScaleY { get; set; }
            public float ScaleZ { get; set; }
            public List<string> RoamingPath { get; set; }
            public List<int> RoamingPathDelay { get; set; }
        }
        #endregion

        #region List View
        private void RespawnData_SelectedIndexChanged(object sender, EventArgs e)
        {
            if (RespawnData.SelectedItems.Count == 0)
                return;

            string selectedFileName = RespawnData.SelectedItems[0].Text;
            string directoryPath = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "Servers", "Data", "Gen");
            string filePath = Path.Combine(directoryPath, selectedFileName);

            try
            {
                string jsonContent = File.ReadAllText(filePath);
                RespawnContents.Text = jsonContent;

                // Deserialize JSON without filtering any entries
                JArray jsonArray = JArray.Parse(jsonContent);
                respawnData = jsonArray.Select(item => new Respawn
                {
                    PosID = (int)item["PosID"],
                    Stage = (long)item["Stage"],
                    GenObjectID = (long)item["GenObjectID"], // Allow 0 as a valid value
                    GenObjectType = (int)item["GenObjectType"],
                    SpawnCount = (int)item["SpawnCount"],
                    SpawnRange = (int)item["SpawnRange"],
                    ActiveType = (int)item["ActiveType"],
                    RegMinTime = (int)item["RegMinTime"],
                    RegMaxTime = (int)item["RegMaxTime"],
                    LocationX = (double)item["LocationX"],
                    LocationY = (double)item["LocationY"],
                    LocationZ = (double)item["LocationZ"],
                    RotationYaw = (int)item["RotationYaw"],
                    LinkGroup = (int)item["LinkGroup"],
                    LinkGroupDistance = (int)item["LinkGroupDistance"],
                    GenType = (int)item["GenType"],
                    FirstGenDelayMin = (int)item["FirstGenDelayMin"],
                    FirstGenDelayMax = (int)item["FirstGenDelayMax"],
                    GroupGenID = (int)item["GroupGenID"],
                    GroupGenCountThreshold = (int)item["GroupGenCountThreshold"],
                    RoamingType = (int)item["RoamingType"],
                    ScaleX = (float)item["ScaleX"],
                    ScaleY = (float)item["ScaleY"],
                    ScaleZ = (float)item["ScaleZ"],
                    RoamingPath = item["RoamingPath"].ToObject<List<string>>(),
                    RoamingPathDelay = item["RoamingPathDelay"].ToObject<List<int>>()
                }).ToList();

                // Clear and populate the RespawnList ComboBox with PosID values
                RespawnList.Items.Clear();
                foreach (var respawn in respawnData)
                {
                    RespawnList.Items.Add(respawn.PosID);
                }

                if (RespawnList.Items.Count > 0)
                {
                    RespawnList.SelectedIndex = 0;
                }
                RespawnList.Items.Add("New Respawn");
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Error reading file {selectedFileName}: {ex.Message}", "File Read Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }
        #endregion

        #region Delete Respawn Buttons
        private void DeleteRespawnButton_Click(object sender, EventArgs e)
        {
            if (RespawnList.SelectedItem == null || respawnData == null)
            {
                MessageBox.Show("No respawn data selected to delete.", "Delete Error", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            // Get the selected PosID from the ComboBox
            int selectedPosID = (int)RespawnList.SelectedItem;

            // Find the respawn entry with the selected PosID
            var respawnToDelete = respawnData.FirstOrDefault(r => r.PosID == selectedPosID);

            if (respawnToDelete != null)
            {
                // Remove the selected respawn entry
                respawnData.Remove(respawnToDelete);

                // Reorder the PosID values to be sequential
                for (int i = 0; i < respawnData.Count; i++)
                {
                    respawnData[i].PosID = i + 1;
                }

                // Update the RespawnList ComboBox
                RespawnList.Items.Clear();
                foreach (var respawn in respawnData)
                {
                    RespawnList.Items.Add(respawn.PosID);
                }

                // Add "New Respawn" option back to the list
                RespawnList.Items.Add("New Respawn");

                // Clear the textboxes
                PosID.Clear();
                MonsterID.Clear();
                SpawnCount.Clear();
                SpawnRange.Clear();
                LocationX.Clear();
                LocationY.Clear();
                LocationZ.Clear();

                // Update the RespawnsContent RichTextBox with the new JSON content
                string updatedJson = JsonConvert.SerializeObject(respawnData, Formatting.Indented);
                RespawnContents.Text = updatedJson;

                // Save the updated JSON back to the file
                if (RespawnData.SelectedItems.Count > 0)
                {
                    string selectedFileName = RespawnData.SelectedItems[0].Text;
                    string directoryPath = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "Servers", "Data", "Gen");
                    string filePath = Path.Combine(directoryPath, selectedFileName);

                    try
                    {
                        File.WriteAllText(filePath, updatedJson);
                        MessageBox.Show($"Respawn data with PosID {selectedPosID} deleted and PosIDs reordered successfully.", "Delete Success", MessageBoxButtons.OK, MessageBoxIcon.Information);
                    }
                    catch (Exception ex)
                    {
                        MessageBox.Show($"Error saving changes: {ex.Message}", "Save Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                    }
                }
            }
            else
            {
                MessageBox.Show($"Respawn data with PosID {selectedPosID} not found.", "Delete Error", MessageBoxButtons.OK, MessageBoxIcon.Warning);
            }
        }
        #endregion

        #region Respawn Drop Down List
        private void RespawnList_SelectedIndexChanged(object sender, EventArgs e)
        {
            if (RespawnList.SelectedItem == null || respawnData == null)
                return;

            // Check if "New Respawn" is selected
            if (RespawnList.SelectedItem.ToString() == "New Respawn")
            {
                // Clear the textboxes for a new entry
                PosID.Clear();
                MonsterID.Clear();
                SpawnCount.Clear();
                SpawnRange.Clear();
                LocationX.Clear();
                LocationY.Clear();
                LocationZ.Clear();

                // Set PosID to the next available number
                int newPosID = respawnData.Any() ? respawnData.Max(r => r.PosID) + 1 : 1;
                PosID.Text = newPosID.ToString();

                return;
            }

            // Otherwise, load the selected respawn data
            int selectedPosID = (int)RespawnList.SelectedItem;
            var selectedRespawn = respawnData.FirstOrDefault(r => r.PosID == selectedPosID);

            if (selectedRespawn != null)
            {
                PosID.Text = selectedRespawn.PosID.ToString();
                MonsterID.Text = selectedRespawn.GenObjectID.ToString();
                SpawnCount.Text = selectedRespawn.SpawnCount.ToString();
                SpawnRange.Text = selectedRespawn.SpawnRange.ToString();
                LocationX.Text = selectedRespawn.LocationX.ToString();
                LocationY.Text = selectedRespawn.LocationY.ToString();
                LocationZ.Text = selectedRespawn.LocationZ.ToString();
            }
        }
        #endregion

        #region Respawn Data Changed (Text Change Events)
        private void MonsterID_TextChanged(object sender, EventArgs e)
        {
            if (selectedRespawn != null && long.TryParse(MonsterID.Text, out long monsterID))
                selectedRespawn.GenObjectID = monsterID;
        }

        private void SpawnCount_TextChanged(object sender, EventArgs e)
        {
            if (selectedRespawn != null && int.TryParse(SpawnCount.Text, out int spawnCount))
                selectedRespawn.SpawnCount = spawnCount;
        }

        private void SpawnRange_TextChanged(object sender, EventArgs e)
        {
            if (selectedRespawn != null && int.TryParse(SpawnRange.Text, out int spawnRange))
                selectedRespawn.SpawnRange = spawnRange;
        }

        private void LocationX_TextChanged(object sender, EventArgs e)
        {
            if (selectedRespawn != null && int.TryParse(LocationX.Text, out int locationX))
                selectedRespawn.LocationX = locationX;
        }

        private void LocationY_TextChanged(object sender, EventArgs e)
        {
            if (selectedRespawn != null && int.TryParse(LocationY.Text, out int locationY))
                selectedRespawn.LocationY = locationY;
        }

        private void LocationZ_TextChanged(object sender, EventArgs e)
        {
            if (selectedRespawn != null && int.TryParse(LocationZ.Text, out int locationZ))
                selectedRespawn.LocationZ = locationZ;
        }
        private void DisableTextChangedEvents()
        {
            MonsterID.TextChanged -= MonsterID_TextChanged;
            SpawnCount.TextChanged -= SpawnCount_TextChanged;
            SpawnRange.TextChanged -= SpawnRange_TextChanged;
            LocationX.TextChanged -= LocationX_TextChanged;
            LocationY.TextChanged -= LocationY_TextChanged;
            LocationZ.TextChanged -= LocationZ_TextChanged;
        }

        private void EnableTextChangedEvents()
        {
            MonsterID.TextChanged += MonsterID_TextChanged;
            SpawnCount.TextChanged += SpawnCount_TextChanged;
            SpawnRange.TextChanged += SpawnRange_TextChanged;
            LocationX.TextChanged += LocationX_TextChanged;
            LocationY.TextChanged += LocationY_TextChanged;
            LocationZ.TextChanged += LocationZ_TextChanged;
        }
        #endregion

        #region Save Respawns Button
        private void SaveButton_Click(object sender, EventArgs e)
        {
            if (RespawnList.SelectedItem == null || RespawnData.SelectedItems.Count == 0)
                return;

            // Check if "New Respawn" is selected
            if (RespawnList.SelectedItem.ToString() == "New Respawn")
            {
                // Determine the new PosID
                int newPosID = int.TryParse(PosID.Text, out int posID) ? posID : (respawnData.Any() ? respawnData.Max(r => r.PosID) + 1 : 1);

                // Get the Stage value from any existing entry
                long stage = respawnData.Any() ? respawnData.First().Stage : 0;

                // Create a new respawn object using values from textboxes or default values
                var newRespawn = new Respawn
                {
                    PosID = newPosID,
                    Stage = stage,
                    GenObjectID = long.TryParse(MonsterID.Text, out long genObjectID) ? genObjectID : 0,
                    GenObjectType = 2,
                    SpawnCount = int.TryParse(SpawnCount.Text, out int spawnCount) ? spawnCount : 1,
                    SpawnRange = int.TryParse(SpawnRange.Text, out int spawnRange) ? spawnRange : 0,
                    ActiveType = 0,
                    RegMinTime = 0,
                    RegMaxTime = 0,
                    LocationX = double.TryParse(LocationX.Text, out double locationX) ? locationX : 0.0,
                    LocationY = double.TryParse(LocationY.Text, out double locationY) ? locationY : 0.0,
                    LocationZ = double.TryParse(LocationZ.Text, out double locationZ) ? locationZ : 0.0,
                    RotationYaw = 249,
                    LinkGroup = 0,
                    LinkGroupDistance = 0,
                    GenType = 0,
                    FirstGenDelayMin = 0,
                    FirstGenDelayMax = 0,
                    GroupGenID = 0,
                    GroupGenCountThreshold = 0,
                    RoamingType = 0,
                    ScaleX = 1.0f,
                    ScaleY = 1.0f,
                    ScaleZ = 1.0f,
                    // Default values for RoamingPath and RoamingPathDelay
                    RoamingPath = new List<string> { "100,200,300", "110,210,310" },
                    RoamingPathDelay = new List<int> { 5, 10 }
                };

                // Add the new respawn object to the list
                respawnData.Add(newRespawn);
                RespawnList.Items.Add(newRespawn.PosID);
                RespawnList.SelectedItem = newRespawn.PosID;
            }

            // Update the JSON content and save it
            string updatedJson = JsonConvert.SerializeObject(respawnData, Formatting.Indented);
            RespawnContents.Text = updatedJson;

            string selectedFileName = RespawnData.SelectedItems[0].Text;
            string directoryPath = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "Servers", "Data", "Gen");
            string filePath = Path.Combine(directoryPath, selectedFileName);

            try
            {
                File.WriteAllText(filePath, updatedJson);
                MessageBox.Show("Changes saved successfully.", "Save Success", MessageBoxButtons.OK, MessageBoxIcon.Information);
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Error saving changes: {ex.Message}", "Save Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }
        #endregion

        #region Delete Gen File Button
        private void DeleteGenFile_Click(object sender, EventArgs e)
        {
            // Check if a file is selected
            if (RespawnData.SelectedItems.Count == 0)
            {
                MessageBox.Show("Please select a JSON file to delete.", "No File Selected", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            // Get the selected file name
            string selectedFileName = RespawnData.SelectedItems[0].Text;
            string directoryPath = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "Servers", "Data", "Gen");
            string filePath = Path.Combine(directoryPath, selectedFileName);

            // Confirm deletion
            DialogResult result = MessageBox.Show($"Are you sure you want to delete the '{selectedFileName}' gen file?", "Confirm Delete", MessageBoxButtons.YesNo, MessageBoxIcon.Question);

            if (result == DialogResult.Yes)
            {
                try
                {
                    // Delete the file
                    if (File.Exists(filePath))
                    {
                        File.Delete(filePath);
                        MessageBox.Show($"The file '{selectedFileName}' has been deleted successfully.", "File Deleted", MessageBoxButtons.OK, MessageBoxIcon.Information);

                        // Refresh the RespawnData ListView
                        LoadRespawnData();
                    }
                    else
                    {
                        MessageBox.Show($"The file '{selectedFileName}' does not exist.", "File Not Found", MessageBoxButtons.OK, MessageBoxIcon.Error);
                    }
                }
                catch (Exception ex)
                {
                    MessageBox.Show($"Error deleting file: {ex.Message}", "Delete Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                }
            }
        }
        #endregion

        #region Export Gen File Button
        private void ExportGenFile_Click(object sender, EventArgs e)
        {
            // Check if a file is selected
            if (RespawnData.SelectedItems.Count == 0)
            {
                MessageBox.Show("Please select a JSON file to export.", "No File Selected", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            // Get the selected file name
            string selectedFileName = RespawnData.SelectedItems[0].Text;
            string directoryPath = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "Servers", "Data", "Gen");
            string sourceFilePath = Path.Combine(directoryPath, selectedFileName);

            // Check if the source file exists
            if (!File.Exists(sourceFilePath))
            {
                MessageBox.Show($"The file '{selectedFileName}' does not exist.", "File Not Found", MessageBoxButtons.OK, MessageBoxIcon.Error);
                return;
            }

            // Open a Save File Dialog
            using (SaveFileDialog saveFileDialog = new SaveFileDialog())
            {
                saveFileDialog.Title = "Export Gen File";
                saveFileDialog.Filter = "JSON Files (*.json)|*.json";
                saveFileDialog.FileName = selectedFileName;

                // Show the dialog and check if the user clicked Save
                if (saveFileDialog.ShowDialog() == DialogResult.OK)
                {
                    string destinationFilePath = saveFileDialog.FileName;

                    try
                    {
                        // Copy the file to the chosen location
                        File.Copy(sourceFilePath, destinationFilePath, overwrite: true);
                        MessageBox.Show($"The file has been exported successfully to '{destinationFilePath}'.", "Export Success", MessageBoxButtons.OK, MessageBoxIcon.Information);
                    }
                    catch (Exception ex)
                    {
                        MessageBox.Show($"Error exporting file: {ex.Message}", "Export Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                    }
                }
            }
        }
        #endregion

        #region Create Gen File Button
        private void CreateNewGenFile_Click(object sender, EventArgs e)
        {
            MessageBox.Show("IN PROGRESS", "IN PROGRESS", MessageBoxButtons.OK, MessageBoxIcon.Warning);
            return;
        }
        #endregion

        #region Filters
        private void RefreshList_Click(object sender, EventArgs e)
        {
            string filterText = FilterGenName.Text.ToLower().Trim();
            string monsterIDFilterText = FilterMonsterID.Text.Trim();

            RespawnData.Items.Clear();

            List<string> filteredFiles = new List<string>();
            int totalMonsterCount = 0;

            // If no filter is applied, show all files
            if (string.IsNullOrWhiteSpace(filterText) && string.IsNullOrWhiteSpace(monsterIDFilterText))
            {
                foreach (string fileName in allJsonFiles)
                {
                    RespawnData.Items.Add(new ListViewItem(fileName));
                }

                totalMonsterCount = CalculateMonsterCount(allJsonFiles);
                FileCountLabel.Text = $"Gen File Count: {allJsonFiles.Count}";
                MonsterCountLabel.Text = $"Monsters Count: {totalMonsterCount}";
                return;
            }

            // Parse the MonsterID filter
            long.TryParse(monsterIDFilterText, out long monsterIDFilter);

            // Filter files based on FilterGenName and/or GenObjectID
            foreach (string fileName in allJsonFiles)
            {
                bool nameMatches = string.IsNullOrWhiteSpace(filterText) || fileName.ToLower().Contains(filterText);
                bool idMatches = false;

                if (!string.IsNullOrWhiteSpace(monsterIDFilterText))
                {
                    // Open the file and search for matching GenObjectID
                    string directoryPath = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "Servers", "Data", "Gen");
                    string filePath = Path.Combine(directoryPath, fileName);

                    if (File.Exists(filePath))
                    {
                        try
                        {
                            // Read the file content and parse JSON
                            string jsonContent = File.ReadAllText(filePath);
                            var jsonArray = JArray.Parse(jsonContent);

                            // Check if any GenObjectID matches the filter
                            idMatches = jsonArray.Any(entry => (long)entry["GenObjectID"] == monsterIDFilter);
                        }
                        catch
                        {
                            // Skip files that fail to parse
                        }
                    }
                }

                // Add the file if it matches either filter
                if (nameMatches && (idMatches || string.IsNullOrWhiteSpace(monsterIDFilterText)))
                {
                    filteredFiles.Add(fileName);
                }
            }

            // Update the ListView with the filtered files
            foreach (string fileName in filteredFiles)
            {
                RespawnData.Items.Add(new ListViewItem(fileName));
            }

            // Update the FileCountLabel with the filtered count
            FileCountLabel.Text = $"Gen File Count: {filteredFiles.Count}";
            MonsterCountLabel.Text = $"Monsters Count: {totalMonsterCount}";
        }
        #endregion

        #region Monster Count
        private int CalculateMonsterCount(List<string> filesToProcess)
        {
            int totalMonsterCount = 0;

            foreach (string fileName in filesToProcess)
            {
                string directoryPath = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "Servers", "Data", "Gen");
                string filePath = Path.Combine(directoryPath, fileName);

                if (File.Exists(filePath))
                {
                    try
                    {
                        // Read the file content and parse JSON
                        string jsonContent = File.ReadAllText(filePath);
                        var jsonArray = JArray.Parse(jsonContent);

                        // Sum up the SpawnCount values from each entry
                        foreach (var entry in jsonArray)
                        {
                            if (entry["SpawnCount"] != null && int.TryParse(entry["SpawnCount"].ToString(), out int spawnCount))
                            {
                                totalMonsterCount += spawnCount;
                            }
                        }
                    }
                    catch
                    {
                        // Skip files that fail to parse
                    }
                }
            }

            return totalMonsterCount;
        }
        #endregion

        #region Open JSON Button
        private void OpenJSONButton_Click(object sender, EventArgs e)
        {
            // Check if a file is selected in the RespawnData list view
            if (RespawnData.SelectedItems.Count == 0)
            {
                MessageBox.Show("Please select a JSON file from the list view.", "No File Selected", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            // Get the selected file name from the list view
            string selectedFileName = RespawnData.SelectedItems[0].Text;
            string directoryPath = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "Servers", "Data", "Gen");
            string filePath = Path.Combine(directoryPath, selectedFileName);

            // Check if the file exists
            if (!File.Exists(filePath))
            {
                MessageBox.Show($"The selected file does not exist:\n{filePath}", "File Not Found", MessageBoxButtons.OK, MessageBoxIcon.Error);
                return;
            }

            try
            {
                // Open the file with the default application
                System.Diagnostics.Process.Start(new System.Diagnostics.ProcessStartInfo
                {
                    FileName = filePath,
                    UseShellExecute = true // Ensures the file is opened with the default app
                });
            }
            catch (Exception ex)
            {
                // Handle any errors that occur while trying to open the file
                MessageBox.Show($"An error occurred while trying to open the file:\n{ex.Message}", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }
        #endregion

        #region Tutorial Link
        private void TutorialLink_LinkClicked(object sender, LinkLabelLinkClickedEventArgs e)
        {
            string url = "https://www.lomcn.net/forum/threads/respawns.113068/";

            try
            {
                // Open the URL in the default web browser
                System.Diagnostics.Process.Start(new System.Diagnostics.ProcessStartInfo
                {
                    FileName = url,
                    UseShellExecute = true // Ensures the link opens in the default browser
                });
            }
            catch (Exception ex)
            {
                // Handle any errors that occur while trying to open the link
                MessageBox.Show($"An error occurred while trying to open the link:\n{ex.Message}", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        #endregion
    }
}