export declare enum Reason {
    Finished = "finished",
    Cancelled = "cancelled"
}
export declare function transition(node: HTMLElement, base: string[], from: string[], to: string[], done?: (reason: Reason) => void): () => void;
