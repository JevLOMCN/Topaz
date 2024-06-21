import Image from 'next/image'

export default function ConditionCard({
  image,
  name,
  level,
  onConditionSelection,
}: {
  image: string
  name: string
  level?: number
  onConditionSelection?: () => void
}) {
  return (
    <button
      onClick={() => onConditionSelection?.()}
      disabled={!onConditionSelection}
      className="flex h-full shrink-0 flex-col items-center justify-start gap-3 rounded p-2 transition-[transform,_background-color] will-change-[transform,_background-color] enabled:hover:scale-105 enabled:hover:bg-primary-400/40"
    >
      <Image
        src={image}
        alt={name}
        width={216}
        height={112}
        className="h-[4.8rem] w-[9.5rem] select-none object-contain sm:h-[7rem] sm:w-[13.5rem] sm:rounded-md"
      />

      <p className="max-w-[8rem] text-center text-xs font-medium text-white sm:max-w-[10rem] sm:text-sm">
        <b className="font-semibold">{name}</b>
        {level ? (
          <>
            <br />
            Lv. {level}
          </>
        ) : (
          <></>
        )}
      </p>
    </button>
  )
}
