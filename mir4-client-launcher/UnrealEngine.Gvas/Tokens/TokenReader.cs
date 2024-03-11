namespace UnrealEngine.Gvas.Tokens;

public class TokenReader
{
    public Token Current => list[index];
    public Token Previous => list[index - 1];
    public byte[] RawData { get; }
    public int SkipAfterOffset { get; set; } = -1;

    private readonly List<Token> list;
    private int index;

    public TokenReader(byte[] rawData, List<Token> list)
    {
        RawData = rawData;
        this.list = list;
    }

    public Token? ReadNext()
    {
        if (SkipAfterOffset > -1)
        {
            while (index < list.Count && list[index].Offset < SkipAfterOffset)
                index++;
            SkipAfterOffset = -1;
        }

        if (index >= list.Count)
            return null;

        return list[index++];
    }

    public Token? ReadNextAfter(int offset)
    {
        for (int i = index; i < list.Count; i++)
        {
            if (list[i].Offset >= offset)
                return list[i];
        }

        return null;
    }

    public void Back(int count = 1)
    {
        index -= count;
    }
}