import discord

ROLES_MESSAGE = 1140727328322879608

async def handleRoleAdd(event: discord.RawReactionActionEvent, user: discord.Member):
    if event.message_id != ROLES_MESSAGE:
        return

    emoji = str(event.emoji)
    if emoji == "🇺🇸":
        await toggleRole(1129148272309702756, user, True)
    if emoji == "🇧🇷":
        await toggleRole(1129148360591425596, user, True)
    if emoji == "📢":
        await toggleRole(1129148508721647657, user, True)

async def handleRoleRemove(event: discord.RawReactionActionEvent, user: discord.Member):
    if event.message_id != ROLES_MESSAGE:
        return
    
    emoji = str(event.emoji)
    if emoji == "🇺🇸":
        await toggleRole(1129148272309702756, user, False)
    if emoji == "🇧🇷":
        await toggleRole(1129148360591425596, user, False)
    if emoji == "📢":
        await toggleRole(1129148508721647657, user, False)

async def toggleRole(roleId: int, user: discord.Member, add: bool):
    role = discord.Object(roleId)

    if (add): return await user.add_roles(role)
    else: return await user.remove_roles(role)