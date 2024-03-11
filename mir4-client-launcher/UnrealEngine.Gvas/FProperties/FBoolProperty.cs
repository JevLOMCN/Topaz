namespace UnrealEngine.Gvas.FProperties;

public class FBoolProperty : FProperty
{
    public bool Value { get; set; }

    internal override void Read(BinaryReader reader, string? propertyName, long fieldLength, string path, string? typeNameHint = null, bool bodyOnly = false, Dictionary<string, string>? typeHints = null)
    {
        if (bodyOnly)
            Value = reader.ReadByte() > 0;
        else
            Value = reader.ReadInt16() > 0;
    }

    internal override void Write(BinaryWriter writer, bool skipHeader)
    {
        writer.Write(Value);
        if (!skipHeader)
            writer.Write((byte) 0);
    }

    protected override IEnumerable<object> SerializeContent()
    {
        yield return Value ? "True" : "False";
    }

    public override object? AsPrimitive() => Value;

    public override void SetValue(object? val) => Value = (bool) val;
}