function Anchor({
  children,
  ...props
}: {
  children: JSX.Element | JSX.Element[]
} & React.HTMLAttributes<HTMLAnchorElement>) {
  return (
    <a {...props} className="cursor-pointer font-bold underline">
      {children}
    </a>
  )
}

function H2({ children }: { children: JSX.Element | JSX.Element[] }) {
  return <h2 className="text-white">{children}</h2>
}

function UL({ children }: { children: JSX.Element | JSX.Element[] }) {
  return <ul className="px-4 text-base font-light text-white">{children}</ul>
}

export function useMDXComponents(components: JSX.Element | JSX.Element[]) {
  return { ul: UL, h2: H2, a: Anchor, ...components }
}
