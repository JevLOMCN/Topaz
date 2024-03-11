namespace UnrealEngine.Gvas;

public class CustomVersion
{
    public Guid Key { get; set; }
    public int Version { get; set; }

    public static CustomVersion ReadFrom(BinaryReader reader)
    {
        var entry = new CustomVersion();
        entry.Key = new Guid(reader.ReadBytes(16));
        entry.Version = reader.ReadInt32();
        return entry;
    }

    public void WriteTo(BinaryWriter writer)
    {
        writer.Write(Key.ToByteArray());
        writer.Write(Version);
    }

    public override string ToString() => $"{nameof(CustomVersion)}[guid={Key:D}; val={Version}]";
}