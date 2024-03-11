using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Data;
using System.Drawing;
using System.Linq;
using System.Text;
using System.Text.RegularExpressions;
using System.Threading.Tasks;
using System.Web;
using System.Windows.Forms;
using UnrealEngine.Gvas;

namespace Mir_4_Launcher
{
    public partial class SettingsForm : Form
    {
        //GuestID as Decimal Bytes
        public static byte[] guestID = new byte[] {
                (byte)71, (byte)117, (byte)101, (byte)115, (byte)116, (byte)73, (byte)68
            };

        //LinkID as Decimal Bytes
        public static byte[] linkID = new byte[] {
                (byte)76, (byte)105, (byte)110, (byte)107, (byte)73,(byte)68
            };

        //Gvas
        private SaveGameFile? gvasFile;
        private SaveGameFile? gvasFile1;

        private string saveFilePath = Path.Combine("MirMobile", "MirMobile", "SaveData", "Saved", "SaveGames", "AccountLocalSave1.sav");
        private string saveFilePath1 = Path.Combine("MirMobile", "MirMobile", "SaveData", "Saved", "SaveGames", "AccountLocalSave2.sav");

        public SettingsForm()
        {
            InitializeComponent();
            UpdatePictureBoxBasedOnDXValue();
            UpdatePictureBoxBasedOnResolution();

            try
            {
                if (File.Exists(saveFilePath))
                    gvasFile = SaveGameFile.LoadFrom(saveFilePath);

                if (File.Exists(saveFilePath1))
                    gvasFile1 = SaveGameFile.LoadFrom(saveFilePath1);

                // Get Both Usernames
                ACID1.Text = GetUsername(gvasFile, saveFilePath);
                ACID2.Text = GetUsername(gvasFile1, saveFilePath1);

                // Get Both Passwords
                ACPW1.Text = GetPassword(gvasFile, saveFilePath);
                ACPW2.Text = GetPassword(gvasFile1, saveFilePath1);
            }
            catch { }
        }

        // Check File and return start position if pattern matches
        public static int FindByteArray(string filePath, byte[] byteArray)
        {
            using (FileStream fileStream = new FileStream(filePath, FileMode.Open)) // Open the save file using File stream
            {
                byte[] fileContent = new byte[fileStream.Length]; // Create a byte array for the length of the file
                fileStream.Read(fileContent, 0, (int)fileStream.Length); // Reads the contents of the file into the fileContent byte array

                int startPos = FindPattern(fileContent, byteArray); // Pass the file content and the byte array to match, if found set the start position as an int
                return startPos; // return the start position
            }
        }

        // Check for Pattern Match
        private static int FindPattern(byte[] content, byte[] pattern)
        {
            for (int i = 0; i <= content.Length - pattern.Length; i++) // Initialize a loop that iterates through the content array
            {
                bool found = true; // Set found to true by default
                for (int j = 0; j < pattern.Length; j++) // Initialize an inner loop that iterates through the pattern array. Check each byte of the pattern against the corresponding byte in the content starting from the current position 
                {
                    if (content[i + j] != pattern[j]) // Checks if the byte at index i + j in the content array does not match the byte at index j in the pattern array.
                    {
                        found = false; // As it doesnt match set found to false and break the loop
                        break;
                    }
                }
                if (found) // If the entire pattern is found at position i in the content array (i.e., if found is still true), the method returns the current position i.
                    return i;
            }
            return -1; // If the pattern is not found within the content array, Return -1 to indicate that the pattern was not found.
        }

        //Get the username from the save file
        public static string GetUsername(SaveGameFile? gvas, string saveFile)
        {
            try
            {
                if (gvas != null)
                {
                    if (gvas.Root.Fields.TryGetValue("AccountLoginInfo", out var info))
                    {
                        if (((UnrealEngine.Gvas.FProperties.FStructProperty)info).Fields.TryGetValue("GuestID", out var id))
                        {
                            string? result = ((UnrealEngine.Gvas.FProperties.FStrProperty)id).Value;
                            if (!string.IsNullOrEmpty(result))
                                return result;
                        }
                    }
                }

                return "Error: Unable to locate the Save File"; // return error unable to locate the file
            }
            catch (Exception ex)
            {
                return "Error:" + ex.Message; // return any exception errors as a string
            }
        }

        //Get the password from the save file
        public static string GetPassword(SaveGameFile? gvas, string saveFile)
        {
            try
            {
                if (gvas != null)
                {
                    if (gvas.Root.Fields.TryGetValue("AccountLoginInfo", out var info))
                    {
                        if (((UnrealEngine.Gvas.FProperties.FStructProperty)info).Fields.TryGetValue("LinkID", out var pass))
                        {
                            string? result = ((UnrealEngine.Gvas.FProperties.FStrProperty)pass).Value;
                            if (!string.IsNullOrEmpty(result))
                                return result;
                        }
                    }
                }

                return "Error: Unable to locate the Save File"; // return error unable to locate the file
            }
            catch (Exception ex)
            {
                return "Error:" + ex.Message; // return any exception errors as a string
            }

        }

        private void UpdatePictureBoxBasedOnDXValue()
        {
            string dxBatchFilePath = Path.Combine("MirMobile", "MirMobile_DirectX.bat");

            // Check if the batch file exists
            if (File.Exists(dxBatchFilePath))
            {
                // Read the contents of the batch file
                string[] lines = File.ReadAllLines(dxBatchFilePath);

                // Flags to track if either -dx11 or -dx12 is found
                bool dx11Found = false;
                bool dx12Found = false;

                // Check each line for the DX value
                foreach (string line in lines)
                {
                    if (line.Contains("-dx11"))
                    {
                        // Set the image for the DX11Button
                        DX11Button.Image = Properties.Resources.DX11Pressed; // Replace DX11Pressed with your image resource for DirectX 11
                        DX11Button.Size = new Size(127, 37); // Set the size of the DX11Button
                        dx11Found = true;
                    }
                    else if (line.Contains("-dx12"))
                    {
                        // Set the image for the DX12Button
                        DX12Button.Image = Properties.Resources.DX12Pressed; // Replace DX12Pressed with your image resource for DirectX 12
                        DX12Button.Size = new Size(127, 37); // Set the size of the DX12Button
                        dx12Found = true;
                    }
                }

                // If neither -dx11 nor -dx12 is found, set default images and sizes
                if (!dx11Found)
                {
                    // Set default image and size for DX11Button
                    DX11Button.Image = Properties.Resources.DX11; // Replace DX11 with your default image resource
                    DX11Button.Size = new Size(100, 30); // Set the default size of the DX11Button
                }

                if (!dx12Found)
                {
                    // Set default image and size for DX12Button
                    DX12Button.Image = Properties.Resources.DX12; // Replace DX12 with your default image resource
                    DX12Button.Size = new Size(100, 30); // Set the default size of the DX12Button
                }
            }
            else
            {
                // Default images and sizes if batch file is not found
                DX11Button.Image = Properties.Resources.DX11; // Replace DX11 with your default image resource
                DX11Button.Size = new Size(100, 30); // Set the default size of the DX11Button
                DX12Button.Image = Properties.Resources.DX12; // Replace DX12 with your default image resource
                DX12Button.Size = new Size(100, 30); // Set the default size of the DX12Button
            }
        }



        //private void DisplayAccountID()
        //{
        //    // Display content of AccountLocalSave1.sav
        //    string saveFilePath1 = Path.Combine("MirMobile", "MirMobile", "SaveData", "Saved", "SaveGames", "AccountLocalSave1.sav");
        //    try
        //    {
        //        // Check if the file exists
        //        if (File.Exists(saveFilePath1))
        //        {
        //            // Read the entire file as bytes
        //            byte[] fileBytes = File.ReadAllBytes(saveFilePath1);

        //            // Find the start and end indexes of the GuestID and LinkID
        //            int guestIdStartIndex = IndexOfBytes(fileBytes, new byte[] { 0x47, 0x75, 0x65, 0x73, 0x74, 0x49, 0x44 });
        //            int linkIdStartIndex = IndexOfBytes(fileBytes, new byte[] { 0x4C, 0x69, 0x6E, 0x6B, 0x49, 0x44 });

        //            // Get the bytes between GuestID and LinkID, excluding ignored bytes
        //            List<byte> accountBytes = new List<byte>();
        //            for (int i = guestIdStartIndex + 7; i < linkIdStartIndex; i++)
        //            {
        //                byte b = fileBytes[i];
        //                if (b == 0x00 || b == 0x0C || b == 0x07 || b == 0x09 || b == 0x0D || b == 0x0E || b == 0x0F || b == 0x20 || b == 0x0A)
        //                    continue;
        //                accountBytes.Add(b);
        //            }

        //            // Convert bytes to string using ASCII encoding
        //            string accountString = Encoding.ASCII.GetString(accountBytes.ToArray());

        //            // Filter out "StrProperty" prefix if present
        //            if (accountString.StartsWith("StrProperty"))
        //            {
        //                accountString = accountString.Substring("StrProperty".Length);
        //            }

        //            // Display the string in the text box
        //            ACID1.Text = accountString;
        //        }
        //        else
        //        {
        //            ACID1.Text = "File not found: " + saveFilePath1;
        //        }
        //    }
        //    catch (Exception ex)
        //    {
        //        // Handle any exceptions and display the error message
        //        ACID1.Text = "Error reading the file: " + ex.Message;
        //    }

        //    // Display content of AccountLocalSave2.sav
        //    string saveFilePath2 = Path.Combine("MirMobile", "MirMobile", "SaveData", "Saved", "SaveGames", "AccountLocalSave2.sav");
        //    try
        //    {
        //        // Check if the file exists
        //        if (File.Exists(saveFilePath2))
        //        {
        //            // Read the entire file as bytes
        //            byte[] fileBytes = File.ReadAllBytes(saveFilePath2);

        //            // Find the start and end indexes of the GuestID and LinkID
        //            int guestIdStartIndex = IndexOfBytes(fileBytes, new byte[] { 0x47, 0x75, 0x65, 0x73, 0x74, 0x49, 0x44 });
        //            int linkIdStartIndex = IndexOfBytes(fileBytes, new byte[] { 0x4C, 0x69, 0x6E, 0x6B, 0x49, 0x44 });

        //            // Get the bytes between GuestID and LinkID, excluding ignored bytes
        //            List<byte> accountBytes = new List<byte>();
        //            for (int i = guestIdStartIndex + 7; i < linkIdStartIndex; i++)
        //            {
        //                byte b = fileBytes[i];
        //                if (b == 0x00 || b == 0x0C || b == 0x07 || b == 0x09 || b == 0x0D || b == 0x0E || b == 0x0F || b == 0x20 || b == 0x0A)
        //                    continue;
        //                accountBytes.Add(b);
        //            }

        //            // Convert bytes to string using ASCII encoding
        //            string accountString = Encoding.ASCII.GetString(accountBytes.ToArray());

        //            // Filter out "StrProperty" prefix if present
        //            if (accountString.StartsWith("StrProperty"))
        //            {
        //                accountString = accountString.Substring("StrProperty".Length);
        //            }

        //            // Display the string in the text box
        //            ACID2.Text = accountString;
        //        }
        //        else
        //        {
        //            ACID2.Text = "File not found: " + saveFilePath2;
        //        }
        //    }
        //    catch (Exception ex)
        //    {
        //        // Handle any exceptions and display the error message
        //        ACID2.Text = "Error reading the file: " + ex.Message;
        //    }
        //}

        //private void DisplayPassword()
        //{
        //    string saveFilePath1 = Path.Combine("MirMobile", "MirMobile", "SaveData", "Saved", "SaveGames", "AccountLocalSave1.sav");
        //    string saveFilePath2 = Path.Combine("MirMobile", "MirMobile", "SaveData", "Saved", "SaveGames", "AccountLocalSave2.sav");

        //    try
        //    {
        //        // Display password for AccountLocalSave1.sav
        //        if (File.Exists(saveFilePath1))
        //        {
        //            byte[] fileBytes1 = File.ReadAllBytes(saveFilePath1);
        //            int linkIdStartIndex1 = IndexOfBytes(fileBytes1, new byte[] { 0x4C, 0x69, 0x6E, 0x6B, 0x49, 0x44 });
        //            int buildNameStartIndex1 = IndexOfBytes(fileBytes1, new byte[] { 0x42, 0x75, 0x69, 0x6C, 0x64, 0x4E, 0x61, 0x6D, 0x65 });
        //            List<byte> passwordBytes1 = new List<byte>();
        //            for (int i = linkIdStartIndex1 + 6; i < buildNameStartIndex1; i++)
        //            {
        //                byte b = fileBytes1[i];
        //                if (b == 0x00 || b == 0x0C || b == 0x07 || b == 0x09 || b == 0x0D || b == 0x0E || b == 0x0F || b == 0x0A || b == 0x20)
        //                    continue;
        //                passwordBytes1.Add(b);
        //            }
        //            string passwordString1 = Encoding.ASCII.GetString(passwordBytes1.ToArray());
        //            if (passwordString1.StartsWith("StrProperty"))
        //            {
        //                passwordString1 = passwordString1.Substring("StrProperty".Length);
        //            }
        //            ACPW1.Text = passwordString1;
        //        }
        //        else
        //        {
        //            ACPW1.Text = "File not found: " + saveFilePath1;
        //        }

        //        // Display password for AccountLocalSave2.sav
        //        if (File.Exists(saveFilePath2))
        //        {
        //            byte[] fileBytes2 = File.ReadAllBytes(saveFilePath2);
        //            int linkIdStartIndex2 = IndexOfBytes(fileBytes2, new byte[] { 0x4C, 0x69, 0x6E, 0x6B, 0x49, 0x44 });
        //            int buildNameStartIndex2 = IndexOfBytes(fileBytes2, new byte[] { 0x42, 0x75, 0x69, 0x6C, 0x64, 0x4E, 0x61, 0x6D, 0x65 });
        //            List<byte> passwordBytes2 = new List<byte>();
        //            for (int i = linkIdStartIndex2 + 6; i < buildNameStartIndex2; i++)
        //            {
        //                byte b = fileBytes2[i];
        //                if (b == 0x00 || b == 0x0C || b == 0x07 || b == 0x09 || b == 0x0D || b == 0x0E || b == 0x0F || b == 0x0A || b == 0x20)
        //                    continue;
        //                passwordBytes2.Add(b);
        //            }
        //            string passwordString2 = Encoding.ASCII.GetString(passwordBytes2.ToArray());
        //            if (passwordString2.StartsWith("StrProperty"))
        //            {
        //                passwordString2 = passwordString2.Substring("StrProperty".Length);
        //            }
        //            ACPW2.Text = passwordString2;
        //        }
        //        else
        //        {
        //            ACPW2.Text = "File not found: " + saveFilePath2;
        //        }
        //    }
        //    catch (Exception ex)
        //    {
        //        ACPW1.Text = "Error reading the file: " + ex.Message;
        //        ACPW2.Text = "Error reading the file: " + ex.Message;
        //    }
        //}

        //// Function to find the index of a sequence of bytes in another byte array
        //private int IndexOfBytes(byte[] searchWithin, byte[] searchFor)
        //{
        //    for (int i = 0; i <= searchWithin.Length - searchFor.Length; ++i)
        //    {
        //        bool found = true;
        //        for (int j = 0; j < searchFor.Length; ++j)
        //        {
        //            if (searchWithin[i + j] != searchFor[j])
        //            {
        //                found = false;
        //                break;
        //            }
        //        }
        //        if (found)
        //            return i;
        //    }
        //    return -1;
        //}

        private void CloseImage_Click(object sender, EventArgs e)
        {
            if (gvasFile != null)
                SaveGvasFile(gvasFile, saveFilePath, ACID1.Text, ACPW1.Text);

            if (gvasFile1 != null)
                SaveGvasFile(gvasFile1, saveFilePath1, ACID2.Text, ACPW2.Text);

            this.Close();
        }

        private void SaveGvasFile(SaveGameFile? gvas, string saveFile, string guestID, string linkID)
        {
            if (gvas.Root.Fields.TryGetValue("AccountLoginInfo", out var info))
            {
                if (((UnrealEngine.Gvas.FProperties.FStructProperty)info).Fields.TryGetValue("GuestID", out var id))
                {
                    id.SetValue(guestID);
                }

                if (((UnrealEngine.Gvas.FProperties.FStructProperty)info).Fields.TryGetValue("LinkID", out var pass))
                {
                    pass.SetValue(linkID);
                }

                gvas.Save(saveFile);
            }
        }

        private void DX11Button_Click(object sender, EventArgs e)
        {
            UpdateDXValue("-dx11");
        }

        private void DX12Button_Click(object sender, EventArgs e)
        {
            UpdateDXValue("-dx12");
        }
        private void UpdateDXValue(string dxValue)
        {
            string currentDirectory = Environment.CurrentDirectory;
            string[] batchFilePaths = {
        Path.Combine(currentDirectory, "MirMobile", "MirMobile_DirectX.bat"),
        Path.Combine(currentDirectory, "MirMobile", "MirMobile_DirectX2.bat")
    };

            foreach (string batchFilePath in batchFilePaths)
            {
                if (File.Exists(batchFilePath))
                {
                    string[] batchFileLines = File.ReadAllLines(batchFilePath);

                    for (int i = 0; i < batchFileLines.Length; i++)
                    {
                        if (batchFileLines[i].Contains("-dx11") || batchFileLines[i].Contains("-dx12"))
                        {
                            // Replace only the DirectX version in the line
                            batchFileLines[i] = batchFileLines[i].Replace("-dx11", dxValue).Replace("-dx12", dxValue);
                            break; // Assuming there's only one occurrence in a line
                        }
                    }

                    // Write the updated content back to the batch file
                    File.WriteAllLines(batchFilePath, batchFileLines);

                    // Update the images based on the new DirectX value
                    UpdatePictureBoxBasedOnDXValue();
                }
                else
                {
                    MessageBox.Show($"{Path.GetFileName(batchFilePath)} not found.", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                }
            }
        }

        private void UpdatePictureBoxBasedOnResolution()
        {
            string dxBatchFilePath = Path.Combine("MirMobile", "MirMobile_DirectX.bat");

            if (File.Exists(dxBatchFilePath))
            {
                string[] lines = File.ReadAllLines(dxBatchFilePath);

                bool windowedFound = lines.Any(line => line.Contains("-Windowed"));
                bool fullscreenFound = lines.Any(line => line.Contains("-Fullscreen"));

                WindowedButton.Image = windowedFound ? Properties.Resources.WindowedButtonPressed : Properties.Resources.WindowedButton;
                FullscreenButton.Image = fullscreenFound ? Properties.Resources.FullscreenButtonPressed : Properties.Resources.FullscreenButton;

                // Set the size of the buttons based on the presence of -Windowed or -Fullscreen
                WindowedButton.Size = windowedFound ? new Size(127, 37) : new Size(118, 38);
                FullscreenButton.Size = fullscreenFound ? new Size(127, 37) : new Size(118, 38);
            }
            else
            {
                // Default images and sizes if batch file is not found
                WindowedButton.Image = Properties.Resources.WindowedButton;
                FullscreenButton.Image = Properties.Resources.FullscreenButton;
                WindowedButton.Size = new Size(118, 38);
                FullscreenButton.Size = new Size(118, 38);
            }
        }

        private void UpdateResolution(string resolution)
        {
            string currentDirectory = Environment.CurrentDirectory;
            string[] batchFilePaths = {
        Path.Combine(currentDirectory, "MirMobile", "MirMobile_DirectX.bat"),
        Path.Combine(currentDirectory, "MirMobile", "MirMobile_DirectX2.bat")
    };

            foreach (string dxBatchFilePath in batchFilePaths)
            {
                if (File.Exists(dxBatchFilePath))
                {
                    string[] batchFileContents = File.ReadAllLines(dxBatchFilePath);

                    for (int i = 0; i < batchFileContents.Length; i++)
                    {
                        if (batchFileContents[i].Contains("-Windowed") || batchFileContents[i].Contains("-Fullscreen"))
                        {
                            batchFileContents[i] = resolution switch
                            {
                                "Windowed" => batchFileContents[i].Replace("-Fullscreen", "-Windowed"),
                                "Fullscreen" => batchFileContents[i].Replace("-Windowed", "-Fullscreen"),
                                _ => batchFileContents[i]
                            };
                            break; // Assuming there's only one occurrence in a line
                        }
                    }

                    File.WriteAllLines(dxBatchFilePath, batchFileContents);
                }
                else
                {
                    MessageBox.Show($"{Path.GetFileName(dxBatchFilePath)} not found.", "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                }
            }

            UpdatePictureBoxBasedOnResolution();
        }

        private void FullscreenButton_Click(object sender, EventArgs e)
        {
            UpdateResolution("Fullscreen");
        }

        private void WindowedButton_Click(object sender, EventArgs e)
        {
            UpdateResolution("Windowed");
        }
    }
}
