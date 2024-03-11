namespace UnrealEngine.Gvas.Exceptions;

public class SaveGameException : Exception
{
    public SaveGameException()
    {
    }

    public SaveGameException(string text) : base(text)
    {
    }
}