$ErrorActionPreference = "Stop"

$Root = Split-Path -Parent $MyInvocation.MyCommand.Path
$Sdk = Join-Path $env:LOCALAPPDATA "Android\Sdk"
$Platform = Join-Path $Sdk "platforms\android-35\android.jar"
$BuildTools = Join-Path $Sdk "build-tools\35.0.0"
$Aapt2 = Join-Path $BuildTools "aapt2.exe"
$D8 = Join-Path $BuildTools "d8.bat"
$Zipalign = Join-Path $BuildTools "zipalign.exe"
$Apksigner = Join-Path $BuildTools "apksigner.bat"
$LocalJdk = Join-Path $Root "jdk17\jdk-17.0.19+10"
$Javac = "javac.exe"
$Jar = "jar.exe"
$Keytool = "keytool.exe"
if (Test-Path (Join-Path $LocalJdk "bin\javac.exe")) {
    $Javac = Join-Path $LocalJdk "bin\javac.exe"
    $Jar = Join-Path $LocalJdk "bin\jar.exe"
    $env:JAVA_HOME = $LocalJdk
    $env:Path = (Join-Path $LocalJdk "bin") + ";" + $env:Path
}
if (Test-Path (Join-Path $LocalJdk "bin\keytool.exe")) {
    $Keytool = Join-Path $LocalJdk "bin\keytool.exe"
}
if ($env:JAVA_HOME) {
    $JavaHomeKeytool = Join-Path $env:JAVA_HOME "bin\keytool.exe"
    if (Test-Path $JavaHomeKeytool) { $Keytool = $JavaHomeKeytool }
}

if (!(Test-Path $Platform)) { throw "No existe android.jar: $Platform" }
if (!(Test-Path $Aapt2)) { throw "No existe aapt2: $Aapt2" }
if (!(Test-Path $D8)) { throw "No existe d8: $D8" }
if (!(Test-Path $Zipalign)) { throw "No existe zipalign: $Zipalign" }
if (!(Test-Path $Apksigner)) { throw "No existe apksigner: $Apksigner" }

$App = Join-Path $Root "app"
$Build = Join-Path $Root "build"
$Obj = Join-Path $Build "obj"
$Classes = Join-Path $Build "classes"
$Dex = Join-Path $Build "dex"
$Apk = Join-Path $Build "apk"
$Release = Join-Path $Root "release"
$Manifest = Join-Path $App "src\main\AndroidManifest.xml"
$Res = Join-Path $App "src\main\res"
$Java = Join-Path $App "src\main\java"
$Package = "com.pandatruck.radio"

Remove-Item $Obj,$Classes,$Dex,$Apk -Recurse -Force -ErrorAction SilentlyContinue
New-Item -ItemType Directory -Force -Path $Obj,$Classes,$Dex,$Apk,$Release | Out-Null
$CompilePlatform = Join-Path $Build "android.jar"
Copy-Item $Platform $CompilePlatform -Force

& $Aapt2 compile --dir $Res -o (Join-Path $Obj "res.zip")
if ($LASTEXITCODE -ne 0) { throw "aapt2 compile fallo" }
& $Aapt2 link -o (Join-Path $Apk "unsigned.apk") -I $CompilePlatform --manifest $Manifest --java $Obj (Join-Path $Obj "res.zip")
if ($LASTEXITCODE -ne 0) { throw "aapt2 link fallo" }

$Sources = @()
$Sources += Get-ChildItem $Java -Recurse -Filter *.java | ForEach-Object { $_.FullName }
$Sources += Get-ChildItem $Obj -Recurse -Filter *.java | ForEach-Object { $_.FullName }
& $Javac -encoding UTF-8 -source 1.8 -target 1.8 -bootclasspath $CompilePlatform -d $Classes -classpath $Obj $Sources
if ($LASTEXITCODE -ne 0) { throw "javac fallo" }

$ClassesJar = Join-Path $Build "classes.jar"
Remove-Item $ClassesJar -Force -ErrorAction SilentlyContinue
Push-Location $Classes
& $Jar cf $ClassesJar .
if ($LASTEXITCODE -ne 0) { throw "jar fallo" }
Pop-Location

& $D8 --lib $CompilePlatform --output $Dex $ClassesJar
if ($LASTEXITCODE -ne 0) { throw "d8 fallo" }
& $Aapt2 link -o (Join-Path $Apk "unsigned-with-dex.apk") -I $CompilePlatform --manifest $Manifest (Join-Path $Obj "res.zip")
if ($LASTEXITCODE -ne 0) { throw "aapt2 link apk fallo" }

Add-Type -AssemblyName System.IO.Compression.FileSystem
$unsignedWithDex = Join-Path $Apk "unsigned-with-dex.apk"
$zip = [System.IO.Compression.ZipFile]::Open($unsignedWithDex, "Update")
$dexFile = Join-Path $Dex "classes.dex"
$entry = $zip.CreateEntry("classes.dex")
$entryStream = $entry.Open()
$fileStream = [System.IO.File]::OpenRead($dexFile)
$fileStream.CopyTo($entryStream)
$fileStream.Dispose()
$entryStream.Dispose()
$zip.Dispose()

$Aligned = Join-Path $Apk "aligned.apk"
& $Zipalign -f -p 4 $unsignedWithDex $Aligned
if ($LASTEXITCODE -ne 0) { throw "zipalign fallo" }

$Keystore = Join-Path $Release "panda-debug.keystore"
if (!(Test-Path $Keystore)) {
    & $Keytool -genkeypair -v -keystore $Keystore -storepass pandatruck -keypass pandatruck -alias panda-debug -keyalg RSA -keysize 2048 -validity 10000 -dname "CN=Panda Truck,O=Panda Truck,C=PA"
}

$Output = Join-Path $Release "PandaTruck-debug.apk"
& $Apksigner sign --ks $Keystore --ks-pass pass:pandatruck --key-pass pass:pandatruck --out $Output $Aligned
if ($LASTEXITCODE -ne 0) { throw "apksigner sign fallo" }
& $Apksigner verify --verbose $Output
if ($LASTEXITCODE -ne 0) { throw "apksigner verify fallo" }

Write-Host "APK creado: $Output"
