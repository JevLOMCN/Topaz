import Image from 'next/image'

export default function LevelFrameSkeleton({ label }: { label: string }) {
  return (
    <label
      className={
        'flex shrink-0 flex-col gap-4 text-center text-sm font-bold text-white md:text-lg'
      }
    >
      {label}

      <div
        className={
          'relative flex h-24 w-24 flex-col items-center justify-center md:h-32 md:w-32'
        }
      >
        <Image
          src={'/images/level-frame.svg'}
          alt=""
          width={136}
          height={136}
          className="pointer-events-none absolute select-none"
        />
      </div>
    </label>
  )
}
