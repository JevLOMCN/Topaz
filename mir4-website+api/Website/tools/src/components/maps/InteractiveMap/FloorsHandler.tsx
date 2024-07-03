export default function FloorsHandler({
  floors,
  activeIndex,
  action,
}: {
  floors: string[]
  activeIndex: number
  action: (floorIndex: number) => void
}) {
  return (
    <div className="absolute right-6 top-28 z-20 ml-auto flex gap-2 rounded-md bg-black/40 p-1 text-sm font-bold text-white backdrop-blur-lg [&>button:hover]:bg-white/5 [&>button]:rounded [&>button]:px-3 [&>button]:py-2 [&>button]:transition-colors">
      {floors.map((label, index) => (
        <button
          key={label}
          data-active={activeIndex === index}
          className="data-[active=true]:bg-white/10"
          onClick={() => action(index)}
        >
          {label}
        </button>
      ))}
    </div>
  )
}
