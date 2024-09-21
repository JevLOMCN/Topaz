using System;
using System.Drawing;
using System.Threading;
using System.Windows.Forms;

namespace Server_Console.Database_Tool
{
    public static class ItemDetailForm
    {
        private static Form? formInstance;
        private static Panel? contentPanel;
        private static PictureBox? iconPictureBox;
        private static Label? nameLabel, iconIdLabel, itemIdLabel, nameValueLabel, iconIdValueLabel, itemIdValueLabel, descLabel;
        private static Button? copyIconIdButton, copyItemIdButton, copyNameButton;

        public static void Initialize()
        {
            formInstance = new Form
            {
                Text = "Item Details",
                Size = new Size(700, 450),
                FormBorderStyle = FormBorderStyle.None,
                BackColor = Color.FromArgb(40, 40, 40),
                StartPosition = FormStartPosition.CenterScreen,
                TopMost = true
            };

            formInstance.Deactivate += (s, e) => formInstance.Hide();

            string nameText = $"{FileManager.GetStringTemplateById(1000147)}:";
            string iconText = "Icon ID:";
            string itemText = $"{FileManager.GetStringMessageById(1300055)} ID:";
            string descriptionText = $"{FileManager.GetStringMessageById(1063677)}:";

            contentPanel = new Panel
            {
                Dock = DockStyle.Fill,
                Padding = new Padding(50),
                BackColor = Color.FromArgb(30, 30, 30)
            };

            iconPictureBox = new PictureBox
            {
                Size = new Size(125, 125),
                Location = new Point(50, 50),
                SizeMode = PictureBoxSizeMode.Zoom,
                BorderStyle = BorderStyle.FixedSingle
            };

            nameLabel = new Label
            {
                Text = nameText,
                Location = new Point(200, 50),
                Size = TextRenderer.MeasureText(nameText, new Font("Segoe UI", 12F)),
                Font = new Font("Segoe UI", 12F),
                ForeColor = Color.White
            };

            nameValueLabel = new Label
            {
                Location = new Point(200, 80),
                Size = new Size(300, 35),
                Font = new Font("Segoe UI", 12F),
                ForeColor = Color.WhiteSmoke
            };

            copyNameButton = CreateImageButton(
                ImageProcessor.GetIconFromUE4("MirMobile/Content/UI/Atlas_N_Pack/Sprites_N/Spr_Common/Ico_Text_copy_Sprite"),
                string.Empty, 550, 80);

            int iconIdStartX = 200;
            int itemIdStartX = 400;

            iconIdLabel = new Label
            {
                Text = iconText,
                Location = new Point(iconIdStartX, 130),
                Size = TextRenderer.MeasureText(iconText, new Font("Segoe UI", 12F)),
                Font = new Font("Segoe UI", 12F),
                ForeColor = Color.WhiteSmoke
            };

            itemIdLabel = new Label
            {
                Text = itemText,
                Location = new Point(itemIdStartX, 130),
                Size = TextRenderer.MeasureText(itemText, new Font("Segoe UI", 12F)),
                Font = new Font("Segoe UI", 12F),
                ForeColor = Color.WhiteSmoke
            };

            iconIdValueLabel = new Label
            {
                Location = new Point(iconIdStartX, 160),
                Size = new Size(100, 30),
                Font = new Font("Segoe UI", 12F),
                ForeColor = Color.WhiteSmoke
            };

            copyIconIdButton = CreateImageButton(
                ImageProcessor.GetIconFromUE4("MirMobile/Content/UI/Atlas_N_Pack/Sprites_N/Spr_Common/Ico_Text_copy_Sprite"),
                string.Empty, iconIdStartX + 150, 160);

            itemIdValueLabel = new Label
            {
                Location = new Point(itemIdStartX, 160),
                Size = new Size(100, 30),
                Font = new Font("Segoe UI", 12F),
                ForeColor = Color.WhiteSmoke
            };

            copyItemIdButton = CreateImageButton(
                ImageProcessor.GetIconFromUE4("MirMobile/Content/UI/Atlas_N_Pack/Sprites_N/Spr_Common/Ico_Text_copy_Sprite"),
                string.Empty, itemIdStartX + 150, 160);

            var descTitleLabel = new Label
            {
                Text = descriptionText,
                Location = new Point(50, 200),
                Size = TextRenderer.MeasureText(descriptionText, new Font("Segoe UI", 12F)),
                Font = new Font("Segoe UI", 12F),
                ForeColor = Color.WhiteSmoke
            };

            descLabel = new Label
            {
                Location = new Point(50, 230),
                Size = new Size(600, 100),
                Font = new Font("Segoe UI", 12F),
                ForeColor = Color.WhiteSmoke
            };

            Button closeButton = new Button
            {
                Size = new Size(30, 30),
                Location = new Point(650, 20),
                BackgroundImage = ImageProcessor.GetIconFromUE4("MirMobile/Content/UI/Atlas_N_Pack/Sprites_N/Spr_Common/Icon_cancel_Sprite"),
                BackgroundImageLayout = ImageLayout.Stretch,
                Cursor = Cursors.Hand,
                FlatStyle = FlatStyle.Flat
            };
            closeButton.FlatAppearance.BorderSize = 0;
            closeButton.Click += (s, e) => formInstance.Hide();

            contentPanel.Controls.Add(iconPictureBox);
            contentPanel.Controls.Add(nameLabel);
            contentPanel.Controls.Add(nameValueLabel);
            contentPanel.Controls.Add(copyNameButton);
            contentPanel.Controls.Add(iconIdLabel);
            contentPanel.Controls.Add(iconIdValueLabel);
            contentPanel.Controls.Add(copyIconIdButton);
            contentPanel.Controls.Add(itemIdLabel);
            contentPanel.Controls.Add(itemIdValueLabel);
            contentPanel.Controls.Add(copyItemIdButton);
            contentPanel.Controls.Add(descTitleLabel);
            contentPanel.Controls.Add(descLabel);
            contentPanel.Controls.Add(closeButton);

            formInstance.Controls.Add(contentPanel);
        }

        public static void UpdateDetails(ItemData item)
        {
            if (ItemPage.CachedItemIcons.ContainsKey(item.ItemID))
                iconPictureBox.Image = ItemPage.CachedItemIcons[item.ItemID];
            else
                iconPictureBox.Image = null;

            nameValueLabel.Text = FileManager.GetStringTemplateById(item.NameSid);

            string iconId = item.Icon.ToString();
            string itemId = item.ItemID.ToString();

            iconIdValueLabel.Text = iconId;
            itemIdValueLabel.Text = itemId;

            iconIdValueLabel.Width = TextRenderer.MeasureText(iconId, iconIdValueLabel.Font).Width;
            itemIdValueLabel.Width = TextRenderer.MeasureText(itemId, itemIdValueLabel.Font).Width;

            descLabel.Text = FileManager.GetStringTemplateById(item.NoteSid);

            copyIconIdButton.Tag = iconId;
            copyItemIdButton.Tag = itemId;
            copyNameButton.Tag = nameValueLabel.Text;
        }

        private static Button CreateImageButton(Image? image, string copyText, int x, int y)
        {
            Button button = new Button
            {
                Size = new Size(30, 30),
                Location = new Point(x, y),
                BackgroundImage = image,
                BackgroundImageLayout = ImageLayout.Stretch,
                Cursor = Cursors.Hand,
                FlatStyle = FlatStyle.Flat,
            };

            button.FlatAppearance.BorderSize = 0;
            button.Click += (s, e) =>
            {
                string textToCopy = (string)button.Tag ?? copyText;
                if (!string.IsNullOrEmpty(textToCopy))
                {
                    Thread staThread = new Thread(() =>
                    {
                        Clipboard.SetText(textToCopy);
                    });
                    staThread.SetApartmentState(ApartmentState.STA);
                    staThread.Start();
                    staThread.Join();
                }
                else
                {
                    MessageBox.Show("Nothing to copy!", "Warning", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                }
            };
            return button;
        }

        public static void ShowItemDetails(ItemData item)
        {
            formInstance.Hide();
            UpdateDetails(item);
            formInstance.Show();
        }
    }
}
