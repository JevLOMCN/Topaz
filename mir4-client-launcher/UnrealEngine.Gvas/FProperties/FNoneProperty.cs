using System.Data;

namespace UnrealEngine.Gvas.FProperties;

public class FNoneProperty : FProperty
{
    private static FNoneProperty? instance;

    public FNoneProperty()
    {
        if (instance == null)
            instance = this;
        else
            throw new ConstraintException($"The {nameof(FNoneProperty)} property can only be created once.");
    }

    internal override void Read(BinaryReader reader, string? propertyName, long fieldLength, string path, string? typeNameHint = null, bool bodyOnly = false, Dictionary<string, string>? typeHints = null)
    {
        throw new NotImplementedException();
    }

    protected override IEnumerable<object> SerializeContent()
    {
        throw new NotImplementedException();
    }
}