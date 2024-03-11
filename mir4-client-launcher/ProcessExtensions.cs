using System;
using System.Diagnostics;
using System.Collections.Generic;
using System.Runtime.InteropServices;

namespace Mir_4_Launcher
{
    public static class ProcessExtensions
    {
        [DllImport("kernel32.dll")]
        private static extern uint GetWindowThreadProcessId(IntPtr hWnd, out uint processId);

        public static int GetParentProcessId(this Process process)
        {
            IntPtr hwnd;
            uint processId;
            try
            {
                hwnd = process.MainWindowHandle;
                GetWindowThreadProcessId(hwnd, out processId);
                return (int)processId;
            }
            catch (Exception ex)
            {
                Console.WriteLine($"Error retrieving parent process ID: {ex.Message}");
                return 0;
            }
        }

        public static Process[] GetChildProcesses(this Process parentProcess)
        {
            List<Process> children = new List<Process>();
            foreach (Process process in Process.GetProcesses())
            {
                if (process.GetParentProcessId() == parentProcess.Id)
                {
                    children.Add(process);
                }
            }
            return children.ToArray();
        }
    }
}