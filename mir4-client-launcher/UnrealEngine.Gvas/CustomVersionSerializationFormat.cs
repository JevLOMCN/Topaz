namespace UnrealEngine.Gvas;

public enum CustomVersionSerializationFormat
{
    Unknown,
    Guids,
    Enums,
    Optimised,
    
    CustomVersion_Automatic_Plus_One,
    Latest = CustomVersion_Automatic_Plus_One - 1
}