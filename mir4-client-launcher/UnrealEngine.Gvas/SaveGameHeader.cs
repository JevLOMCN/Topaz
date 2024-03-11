using System.Text;
using UnrealEngine.Gvas.Exceptions;

namespace UnrealEngine.Gvas;

public class SaveGameHeader
{
    private const string FileTypeTag = "GVAS";

    public int SaveGameFileVersion { get; set; }
    public int PackageFileVersion { get; set; }
    public int Unknown1 { get; set; }
    public EngineVersion? SavedEngineVersion { get; set; }
    public CustomVersionSerializationFormat CustomVersionFormat { get; set; }
    public List<CustomVersion> CustomVersions { get; set; } = new();
    public string? SaveGameClassName { get; set; }
    public long Unknown2 { get; set; }

    public static SaveGameHeader ReadFrom(BinaryReader reader)
    {
        var magic = reader.ReadChars(4);
        if (!magic.SequenceEqual(FileTypeTag))
            throw new SaveGameException("Invalid magic number in file header, expected: 'GVAS'.");

        var header = new SaveGameHeader();
        header.SaveGameFileVersion = reader.ReadInt32();
        header.PackageFileVersion = reader.ReadInt32();
        if (header.SaveGameFileVersion >= 3)
            header.Unknown1 = reader.ReadInt32(); // Unknown value in UE5 save header
        header.SavedEngineVersion = EngineVersion.ReadFrom(reader);
        header.CustomVersionFormat = (CustomVersionSerializationFormat)reader.ReadInt32();
        header.CustomVersions = Enumerable.Range(0, reader.ReadInt32()).Select(_ => CustomVersion.ReadFrom(reader)).ToList();
        header.SaveGameClassName = reader.ReadFString();
        header.Unknown2 = 0;
        // if (header.SaveGameFileVersion >= 3)
        //     header.Unknown2 = reader.ReadInt64();
        return header;
    }

    public void WriteTo(BinaryWriter writer)
    {
        writer.Write(Encoding.ASCII.GetBytes(FileTypeTag));
        writer.Write(SaveGameFileVersion);
        writer.Write(PackageFileVersion);
        SavedEngineVersion!.WriteTo(writer);
        writer.Write((int)CustomVersionFormat);
        writer.Write(CustomVersions.Count);
        foreach (var customVersion in CustomVersions)
            customVersion.WriteTo(writer);
        writer.WriteFString(SaveGameClassName);
        if (SaveGameFileVersion >= 3)
            writer.Write(Unknown2);
    }

    public override string ToString() => $"File={SaveGameFileVersion}; " +
                                         $"Package={PackageFileVersion}; " +
                                         $"UE={SavedEngineVersion!.Major}.{SavedEngineVersion!.Minor}.{SavedEngineVersion!.Patch}.{SavedEngineVersion!.Build} " +
                                         $"[{SavedEngineVersion.Branch}]";
}