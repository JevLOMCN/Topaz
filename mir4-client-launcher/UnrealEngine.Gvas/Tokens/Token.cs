namespace UnrealEngine.Gvas.Tokens;

public class Token
{
    public string Text { get; }
    public int Offset { get; }

    public Token(string text, int offset)
    {
        Text = text;
        Offset = offset;
    }

    public override string ToString() => Text;

    public static bool operator ==(Token? token, string str) => token?.Text == str;
    public static bool operator !=(Token? token, string str) => token?.Text != str;

    public static implicit operator string?(Token? token) => token?.Text;

    public override int GetHashCode() => HashCode.Combine(Text, Offset);

    public override bool Equals(object? obj)
    {
        return obj switch
        {
            Token token => token.Offset == Offset && token.Text == Text,
            string str => str == Text,
            _ => false
        };
    }
}