namespace UnrealEngine.Gvas.FProperties;

public class FEnumProperty : FProperty
{
    public string? EnumType { get; set; }
    public string? Value { get; set; }

    private bool hasCompactName = false;

    internal override void Read(BinaryReader reader, string? propertyName, long fieldLength, string path, string? typeNameHint = null, bool bodyOnly = false, Dictionary<string, string>? typeHints = null)
    {
        EnumType = reader.ReadFString()!;
        if (EnumType.Contains("::"))
        {
            hasCompactName = true;
            Value = EnumType.Split("::")[1];
            EnumType = EnumType.Split("::")[0];
        }
        else
        {
            hasCompactName = false;
            reader.ReadByte();
            Value = reader.ReadFString()!;
            if (Value.Contains("::"))
            {
                EnumType = Value.Split("::")[0];
                Value = Value.Split("::")[1];
            }
        }
    }

    internal override void Write(BinaryWriter writer, bool skipHeader)
    {
        if (hasCompactName)
        {
            writer.WriteFString($"{EnumType}::{Value}");
        }
        else
        {
            writer.WriteFString(EnumType);
            writer.Write((byte) 0);
            writer.WriteFString($"{EnumType}::{Value}");
        }
    }

    protected override IEnumerable<object> SerializeContent()
    {
        yield return Value ?? string.Empty;
    }

    public override object? AsPrimitive() => Value;

    public override void SetValue(object? val) => Value = (string) val;
}