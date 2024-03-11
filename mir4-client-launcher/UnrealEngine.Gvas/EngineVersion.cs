namespace UnrealEngine.Gvas;

public class EngineVersion
{
    public short Major { get; set; }
    public short Minor { get; set; }
    public short Patch { get; set; }
    public int Build { get; set; }
    public string? Branch { get; set; }

    internal static EngineVersion ReadFrom(BinaryReader reader)
    {
        var version = new EngineVersion();

        version.Major = reader.ReadInt16();
        version.Minor = reader.ReadInt16();
        version.Patch = reader.ReadInt16();
        version.Build = reader.ReadInt32();
        version.Branch = reader.ReadFString();

        return version;
    }

    public void WriteTo(BinaryWriter writer)
    {
        writer.Write(Major);
        writer.Write(Minor);
        writer.Write(Patch);
        writer.Write(Build);
        writer.WriteFString(Branch);
    }
}