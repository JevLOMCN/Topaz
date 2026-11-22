<h1 align="center">
Mir 4 - Official Public Topaz Source
</h2>

<h3 align="center">

[![Latest Release](https://img.shields.io/github/v/release/JevLOMCN/mir4?label=release&style=flat-square)](https://github.com/JevLOMCN/mir4/releases/latest)
[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg?style=flat-square)](LICENSE)
![Build](Tools/icons/badge.svg)
![MySQL](https://img.shields.io/badge/mysql-4479A1.svg?style=for-the-badge&logo=mysql&logoColor=white)
![C++](https://img.shields.io/badge/c++-%2300599C.svg?style=for-the-badge&logo=c%2B%2B&logoColor=white)
![Discord](https://img.shields.io/badge/Discord-%235865F2.svg?style=for-the-badge&logo=discord&logoColor=white)
</h3>

<h2 align="center">
Topaz Mir 4 is an open-source implementation of the official Legend of Mir 4 server code that empowers community organizers, modders, and hobbyists to spin up their own private Mir 4 server in minutes. <br><br>
Built directly from the official sources for maximum compatibility, using MySQL for data storage and JSON files for server configuration, with simple setup scripts and detailed guides/wikis. <br><br>
Its friendly architecture lets you tweak rates, items, stats, and more, while an active LOMCN community provides a hub for sharing setups, reporting bugs, and collaborating on new features. <br><br>
No NFTs. No crypto. Just a clean, moddable Mir 4 experience built by and for the community. <br><br>
Dive in to explore, customize, and host your personalized Legend of Mir 4 experience today!
</h2>

> [!IMPORTANT]  
> Server Source is NOT available at this time. Please stand by for us to Open Source it.

> [!WARNING]  
> Anyone claiming to have the Mir 4 Source code is trying to scam you! [Never buy files](https://forum.ragezone.com/threads/stay-safe-list-of-known-mmorpg-scammers-and-fake-file-sellers.1233633/).

---

## 📦 Codebase Composition

| Source                | Lines of Code |      Share |
| :-------------------- | ------------: | ---------: |
| **WEMADE (Original)** |   **308,737** |  **3.46%** |
| **Topaz Project**     | **8,926,627** | **96.54%** |

> ### 📈 Project Evolution
>
> Although this project began with the leaked 2019 WEMADE server source, years of development have transformed it into a substantially new codebase.
>
> Today, **over 96% of the repository consists of new or heavily modified code** written by the Topaz team. This work includes:
>
> * 🏗️ Rebuilding missing server systems from scratch
> * 🎮 Implementing game systems introduced between the **2021 global release** and the **2026 client**
> * 🌐 Extensive networking and protocol updates
> * 🖥️ Server architecture improvements and refactoring
> * 🗄️ Database redesigns and optimization
> * 🔒 Security enhancements and anti-exploit measures
> * 🐞 Thousands of bug fixes, stability improvements, and performance optimizations
>
> Many core systems simply did not exist in the original leaked source and had to be recreated through reverse engineering, research, and implementation to achieve compatibility with the modern MIR4 client.
>
> The remaining **3.46%** represents original WEMADE source that remains largely unchanged.
>
> *Statistics exclude generated files, binaries, third-party libraries, metadata, and configuration files.*

---

> **Fun fact:** The Topaz codebase is now approximately **29× larger** than the original WEMADE source it was built upon.

---

## 📂 Folder Structure
- [**Tools**](/Tools)  
  A collection of useful MIR4 tools, ranging from premade websites to client editors.  
- [**mir4-client-launcher**](/mir4-client-launcher)  
  An optional client launcher, designed to look and feel like the official MIR4 launcher.  
- [**mir4-db-sps**](/mir4-db-sps)  
  Complete MIR4 server database exports, including all schemas and stored procedures.  
- [**mir4-server-console**](/mir4-server-console)  
  An optional server launcher with quick-edit tools for live tweaking.  
- [**mir4-server-jsons**](/mir4-server-jsons)  
  Full set of MIR4 server JSON configuration files.

---

<h2 align="center">🔗 Quick Links</h2>

<p align="center">
  Everything you need to access, learn, and manage the project in one place.
</p>

---

### 🌐 Official Resources

<table align="center">
  <tr>
    <td align="center">🌐</td>
    <td><a href="https://thelegendofmir.uk/Topaz/">Official Project Site</a></td>
  </tr>
  <tr>
    <td align="center">🌐</td>
    <td><a href="https://thelegendofmir.uk/mir4/Website">Test Website</a></td>
  </tr>
  <tr>
    <td align="center">📖</td>
    <td><a href="https://thelegendofmir.uk/Topaz/wiki">Server Wiki</a></td>
  </tr>
  <tr>
    <td align="center">📖</td>
    <td><a href="https://thelegendofmir.uk/Topaz/wiki#database">Server Database</a></td>
  </tr>
  <tr>
    <td align="center">
      <img src="https://www.mir4tools.com/favicon.ico" width="18"/>
    </td>
    <td><a href="https://www.mir4tools.com/">Mir4 Tools</a></td>
  </tr>
  <tr>
    <td align="center">
      <img src="https://cdn.prod.website-files.com/6257adef93867e50d84d30e2/66e3d80db9971f10a9757c99_Symbol.svg" width="18"/>
    </td>
    <td><a href="https://thelegendofmir.uk/Topaz/redirects/discord">Discord</a></td>
  </tr>
  <tr>
    <td align="center">▶️</td>
    <td><a href="https://youtu.be/_f9N_MuEFb0">Server Setup Guide (YouTube)</a></td>
  </tr>
  <tr>
    <td align="center">📚</td>
    <td><a href="https://thelegendofmir.uk/Topaz/docs#download">Server Setup Guide (Written)</a></td>
  </tr>
</table>


### 💬 LOMCN Community

<table align="center">
  <tr>
    <td>📚</td>
    <td><a href="https://www.lomcn.net/forum/forums/topaz-mir-4-tutorials.847/">Tutorials</a></td>
  </tr>
  <tr>
    <td>📦</td>
    <td><a href="https://www.lomcn.net/forum/forums/topaz-mir-4-releases.848/">Releases</a></td>
  </tr>
  <tr>
    <td>🐞</td>
    <td><a href="https://www.lomcn.net/forum/forums/topaz-mir-4-bug-reports.849/">Bug Reports</a></td>
  </tr>
  <tr>
    <td>💬</td>
    <td><a href="https://www.lomcn.net/forum/forums/topaz-mir-4-help.850/">Help</a></td>
  </tr>
  <tr>
    <td>🛠️</td>
    <td><a href="https://www.lomcn.net/forum/forums/topaz-mir-4-updates.851/">Updates</a></td>
  </tr>
</table>

---

## Dependencies

| Component              | Supported OS                          | Required Packages & Links                                                                                                                                                                                                                                                                                   |
|------------------------|---------------------------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Server (Windows)**   | Windows 10+<br>Windows Server 2016+    | - [MySQL Community 8.0.42](https://dev.mysql.com/downloads/installer/) <br>- [Couchbase Community 7.2](https://packages.couchbase.com/releases/7.2.0/couchbase-server-enterprise_7.2.0-windows_amd64.msi) <br>- [Memurai](https://www.memurai.com/get-memurai) <br>- [Windows Terminal](https://aka.ms/terminal) <br>- [Microsoft Visual C++ Redistributable](https://learn.microsoft.com/en-us/cpp/windows/latest-supported-vc-redist?view=msvc-170) |
| **Server (Linux)**     | Ubuntu 18.04+ / CentOS 7+             | - [MySQL Community 8.0.42](https://dev.mysql.com/downloads/installer/) <br>- [Couchbase Community 7.2](https://docs.couchbase.com/server/current/install/install-linux.html) <br>- [Memurai](https://www.memurai.com/get-memurai)             |
| **Client (Windows)**   | Windows 8.1 / 10 / 11                 | - [DirectX Runtime](https://www.microsoft.com/en-us/download/details.aspx?id=35) <br>- [.NET Desktop Runtime](https://dotnet.microsoft.com/en-us/download/dotnet/thank-you/runtime-desktop-8.0.15-windows-x64-installer)                                                                                                                                            |
| **Optional**           | —                                     | - cPanel (Website)<br>- [Unreal Engine 4.24.3](https://docs.unrealengine.com/4.24/en-US/)<br>- [Plugins List](https://mirfiles.com/resources/mir2/users/Jev/Mir%204/MirMobile.upluginmanifest.txt)                                                                                                                                                                                                  |

---

## Compatibility

| Function           | Windows | Linux | MacOS | Required       |
|--------------------|---------|-------|-------|----------------|
| Servers            | ✔️      | ➖    | ➖    | ✔️             |
| Client             | ✔️      | ➖    | ➖    | ✔️             |
| Server Console     | ✔️      | ✔️    | ✔️    | ❌             |
| Client Launcher    | ✔️      | ❌    | ❌    | ❌             |
| Website            | ✔️      | ✔️    | ✔️    | ❌             |

**✔️** = Supported | **➖** = To be tested | **❌** = Not supported  

---

<h2 align="center">👨‍💻 Development Team</h2>

<p align="center">
  <a href="https://www.lomcn.net/forum/members/jev.29880/">
    <img src="https://avatars.githubusercontent.com/u/68875342" width="110"><br>
    <b>Jev</b><br>
  </a>
  &nbsp;&nbsp;&nbsp;&nbsp;

  <a href="https://www.lomcn.net/forum/members/meacher.3993/">
    <img src="https://media0.giphy.com/media/aqFRBqGjnznd6/200w.gif" width="110"><br>
    <b>Meacher</b><br>
  </a>
  &nbsp;&nbsp;&nbsp;&nbsp;

  <a href="https://www.lomcn.net/forum/members/1pkryan.13050/">
    <img src="https://www.lomcn.net/forum/data/avatars/l/13/13050.jpg?1680916386" width="110"><br>
    <b>1PKRyan</b><br>
  </a>

  <a href="https://www.lomcn.net/forum/members/estregoik.45841/">
    <img src="https://66.media.tumblr.com/725aeaf36ff6262f947aa945164e49a2/tumblr_pfnyfnGjG81wzypxlo1_640.gif" width="110"><br>
    <b>Wagner</b><br>
  </a>
  &nbsp;&nbsp;&nbsp;&nbsp;

  <a href="https://www.lomcn.net/forum/members/bughyt.46860/">
    <img src="https://media.tenor.com/GtmGLCw1SmUAAAAM/buggriddy-lusgifs.gif" width="110"><br>
    <b>BughyT</b><br>
  </a>
  &nbsp;&nbsp;&nbsp;&nbsp;

  <a href="https://www.lomcn.net/forum/members/chriz.86/">
    <img src="https://encrypted-tbn2.gstatic.com/images?q=tbn:ANd9GcTNs5tTmCNN6AXCXUspYleX_-LLN_-c1UPECpVnPHfy9C0V3AlS" width="110"><br>
    <b>Chriz</b><br>
  </a>

  <a href="https://www.lomcn.net/forum/members/damian.1126/">
    <img src="https://media2.giphy.com/media/HbU1apZE5zopB0e8Hq/giphy-downsized.gif" width="110"><br>
    <b>CodePwr</b><br>
  </a>
</p>

---

<h2 align="center">🤝 Contributors</h2>

<table align="center">
<tr>
<td align="center" width="220">
<b><a href="https://www.lomcn.net/forum/members/netskee.19772/">Netskee</a></b><br>
<sub>🎨 Graphic Design</sub>
</td>

<td align="center" width="220">
<b><a href="https://www.lomcn.net/forum/members/aboveyou.45200/">Firev2 (AboveYou)</a></b><br>
<sub>📦 Client & Server JSON Sourcing</sub>
</td>

<td align="center" width="220">
<b><a href="https://www.lomcn.net/forum/members/mental.3870/">Mental</a></b><br>
<sub>🤝 Outsourcing, Sponsorship & Advertising</sub>
</td>
</tr>

<tr>
<td align="center">
<b><a href="https://www.lomcn.net/forum/members/gurgell.45127/">Gurgel</a></b><br>
<sub>🎬 Art, Videos & Graphics</sub>
</td>

<td align="center">
<b><a href="https://www.lomcn.net/forum/members/yangboy.45108/">carolyangbb</a></b><br>
<sub>🤖 GVAS Logic & Bot Research</sub>
</td>

<td align="center">
<b><a href="https://www.mir4tools.com/">Treffy</a></b><br>
<sub>🛠️ Mir4Tools & Bot Collaboration</sub>
</td>
</tr>

<tr>
<td align="center">
<b><a href="https://www.lomcn.net/forum/members/sumiao.42897/">Sumiao</a></b><br>
<sub>🖥️ Server Console Integration</sub>
</td>

<td align="center">
<b><a href="https://github.com/s4oul">S4oul</a></b><br>
<sub>💻 C++ Development</sub>
</td>

<td align="center">
<b><a href="https://forum.ragezone.com/members/cmb.330743/">cmb</a></b><br>
<sub>💻 C++ Development</sub>
</td>
</tr>

<tr>
<td align="center">
<b><a href="https://www.lomcn.net/forum/members/hells.7536/">Hells</a></b><br>
<sub>👥 Outsourcing Developers</sub>
</td>

<td align="center">
<b><a href="https://github.com/TheAlexLichter">manniL</a></b><br>
<sub>📚 Operates <a href="https://www.mir4.wiki/">Mir4.Wiki</a><br>Documentation & Community Resources</sub>
</td>

<td align="center">
<b><a href="https://github.com/JevLOMCN/mir4-launcher/graphs/contributors">Community Contributors</a></b><br>
<sub>❤️ Everyone who has contributed</sub>
</td>
</tr>
</table>

---

## Other projects

<div align="center">
  <img src="https://www.lomcn.net/forum/data/assets/logo/favicon2019_Xf.png" width="32" alt="LOMCN">
</div>

<table align="center" width="100%">

  <tr>
    <td width="48" align="center" valign="top">
      <a href="https://github.com/JevLOMCN/mir1/">
        <img src="https://raw.githubusercontent.com/JevLOMCN/mir4/main/Tools/icons/mir1.png" width="32" height="32">
      </a>
    </td>
    <td>
      <b>Mir 1</b> •
      <a href="https://github.com/JevLOMCN/mir1/">Project</a> •
      <a href="https://github.com/Suprcode/Carbon.Database">Database</a><br>
      Remake of ActozSoft's 1997 <i>The Legend Of Mir 1</i>
    </td>
  </tr>

  <tr>
    <td width="48" align="center" valign="top">
      <a href="https://github.com/Suprcode/Crystal">
        <img src="https://raw.githubusercontent.com/JevLOMCN/mir4/main/Tools/icons/mir2.png" width="32" height="32">
      </a>
    </td>
    <td>
      <b>Mir 2</b> •
      <a href="https://github.com/Suprcode/Crystal">Project</a> •
      <a href="https://github.com/Suprcode/Crystal.Database">Database</a> •
      <a href="https://github.com/Suprcode/Crystal.MapEditor">Map Editor</a><br>
      Remake of ActozSoft / Wemade Entertainment's 1999 <i>The Legend Of Mir 2</i>
    </td>
  </tr>

  <tr>
    <td width="48" align="center" valign="top">
      <a href="https://github.com/Suprcode/Zircon">
        <img src="https://raw.githubusercontent.com/JevLOMCN/mir4/main/Tools/icons/mir3.png" width="32" height="32">
      </a>
    </td>
    <td>
      <b>Mir 3</b> •
      <a href="https://github.com/Suprcode/Zircon">Project</a> •
      <a href="https://mirfiles.com/resources/mir3/zircon/Database.7z">Database</a> •
      <a href="https://www.lomcn.net/forum/threads/map-editor.109317/">Map Editor</a><br>
      Remake of Wemade Entertainment's 2003 <i>The Legend Of Mir 3</i>
    </td>
  </tr>

  <tr>
    <td width="48" align="center" valign="top">
      <a href="https://www.lomcn.net/forum/forums/woool-development-project-onyx.857/">
        <img src="https://raw.githubusercontent.com/JevLOMCN/mir4/main/Tools/icons/woool.png" width="32" height="32">
      </a>
    </td>
    <td>
      <b>WoOOL</b> •
      <a href="https://www.lomcn.net/forum/forums/woool-development-project-onyx.857/">Project</a> •
      <a href="https://www.lomcn.net/forum/threads/nmp-viewer-editor.114580">Map Editor</a><br>
      Remake of Shanda Games' (Shengqu Games) 2003 <i>The World Of Legend</i>
    </td>
  </tr>

  <tr>
    <td width="48" align="center" valign="top">
      <a href="https://github.com/mir-ethernity/mir-eternal">
        <img src="https://raw.githubusercontent.com/JevLOMCN/mir4/main/Tools/icons/mir3d.png" width="32" height="32">
      </a>
    </td>
    <td>
      <b>Mir 3D</b> •
      <a href="https://github.com/JevLOMCN/CQYH">Project</a><br> •
      Remake of Shanda Games' 2016 <i>Legend Eternal</i>
    </td>
  </tr>

  <tr>
    <td width="48" align="center" valign="top">
      <a href="https://github.com/JevLOMCN/mir4">
        <img src="https://raw.githubusercontent.com/JevLOMCN/mir4/main/Tools/icons/mir4.png" width="32" height="32">
      </a>
    </td>
    <td>
      <b>Mir 4</b> •
      <a href="https://github.com/JevLOMCN/mir4">Project</a><br>
      Remake of Wemade Entertainment's 2021 <i>Mir 4</i>
    </td>
  </tr>

  <tr>
    <td width="48" align="center" valign="top">
      <a href="https://mirfiles.co.uk/resources/mir2/users/Jev/Mir%205/Leaks/">
        <img src="https://raw.githubusercontent.com/JevLOMCN/mir4/main/Tools/icons/mir5.png" width="32" height="32">
      </a>
    </td>
    <td>
      <b>Mir 5</b> •
      <a href="https://github.com/JevLOMCN/DarkSteel">Project</a> •
      <a href="https://mirfiles.co.uk/resources/mir2/users/Jev/Mir%205/Leaks/">Leaked Files</a> •
      <a href="https://mirfiles.co.uk/resources/mir2/users/Jev/Mir%205/Leaks/Mir5%20Alpha%202.mkv">Gameplay Video (24m)</a><br>
      Remake of Wemade Entertainment's unreleased <i>Mir 5</i>
    </td>
  </tr>

</table>

---

<h2 align="center">💖 Sponsored By</h2>

<table align="center">
<tr>

<td align="center" width="33%">
<a href="https://forum.ragezone.com/">
<img src="https://forum.ragezone.com/data/styles/10/styles/ragezone/logo.webp" height="60"><br><br>
<b>RageZone</b>
</a>

Leading MMORPG development community featuring reverse engineering, open-source projects, tutorials, server resources, and an active community of developers.

</td>

<td align="center" width="33%">
<a href="https://www.lomcn.net/">
<img src="https://www.lomcn.net/forum/styles/uix/images/logo2024_100.png" height="60"><br><br>
<b>LOMCN</b>
</a>

The largest English-speaking Legend of Mir community, providing forums, open-source projects, technical resources, and years of support for the Mir development scene.

</td>

<td align="center" width="33%">
<a href="https://ptservergames.com/">
<img src="https://ptservergames.com/images/logo_ptserver.png" height="60"><br><br>
<b>PTS Games</b>
</a>

A long-running gaming community founded in 2011. PTS Games generously sponsors the project by hosting our **European game server** free of charge, helping keep MIR4 Online accessible to players across Europe.

</td>

</tr>
</table>

---

## ❤️ We Support Stop Killing Games

<p align="center">
  <a href="https://www.stopkillinggames.com/">
    <img src="https://upload.wikimedia.org/wikipedia/en/1/19/Stop_Killing_Games_logo_%282025%29.png" alt="Stop Killing Games" width="90">
  </a>
</p>

**The Topaz team proudly supports the *Stop Killing Games* initiative.**

Stop Killing Games is a consumer movement campaigning against the practice of publishers permanently disabling games after they have been sold. The campaign advocates for consumer rights, game preservation, and ensuring that legally purchased games remain playable even after official support ends.

As a community-driven preservation project, we share these values and believe that online games deserve a future beyond their commercial lifespan.

<p align="center">
  <a href="https://www.stopkillinggames.com/"><b>🌐 Learn More About Stop Killing Games</b></a>
</p>

---

## Star History

<a href="https://www.star-history.com/?repos=JevLOMCN%2Fmir4&type=timeline&legend=top-left">
 <picture>
   <source media="(prefers-color-scheme: dark)" srcset="https://api.star-history.com/chart?repos=JevLOMCN/mir4&type=timeline&theme=dark&legend=top-left" />
   <source media="(prefers-color-scheme: light)" srcset="https://api.star-history.com/chart?repos=JevLOMCN/mir4&type=timeline&legend=top-left" />
   <img alt="Star History Chart" src="https://api.star-history.com/chart?repos=JevLOMCN/mir4&type=timeline&legend=top-left" />
 </picture>
</a>

---

### 📄 Disclaimer

Please read our **[Disclaimer](./DISCLAIMER.md)** before using this project.
