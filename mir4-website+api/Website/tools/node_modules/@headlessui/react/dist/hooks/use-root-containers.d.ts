import React, { MutableRefObject } from 'react';
export declare function useRootContainers({ defaultContainers, portals, }?: {
    defaultContainers?: (HTMLElement | null | MutableRefObject<HTMLElement | null>)[];
    portals?: MutableRefObject<HTMLElement[]>;
}): {
    resolveContainers: () => HTMLElement[];
    contains: (element: HTMLElement) => boolean;
    mainTreeNodeRef: React.MutableRefObject<HTMLDivElement | null>;
    MainTreeNode: () => JSX.Element;
};
