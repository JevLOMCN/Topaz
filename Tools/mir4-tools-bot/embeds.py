import discord

prepareReportEmbed = discord.Embed(title=f"Contact us", color=0x2C2542, type="rich", description="Interact with the buttons bellow to either make a suggestion or a bug report, the bot will send you a private message after the interaction so you can describe the issue.")

suggestionEmbed = discord.Embed(title=f"Suggestion creation", color=0x2C2542, type="rich", description="Type your suggestion in only one message. If you want to cancel this interaction, click on the Cancel button.\n\nThis interaction will expire after 15 minutes.")
bugReportEmbed = discord.Embed(title=f"Bug report", color=0x2C2542, type="rich", description="Type your report in only one message as the following template:\n\n Page name: Constitution \n Feature name: Tier selection \n How to reproduce: ... \n\n If you want to cancel this interaction, click on the Cancel button.\n\nThis interaction will expire after 15 minutes.")