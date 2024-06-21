import LevelFrameSkeleton from './LevelFrame/skeleton'

export default function LevelCalculationsSkeleton() {
  return (
    <section className="relative flex w-full max-w-3xl flex-col items-center justify-between gap-4">
      <div className="flex w-full items-end justify-between gap-4 md:items-center">
        <LevelFrameSkeleton label={'Current Level'} />

        <div className="hidden h-full w-full grid-rows-[1fr_4px_1fr] flex-col items-center gap-4 pt-11 md:grid">
          <span className="mx-auto mt-auto w-24 animate-pulse rounded-full bg-primary-500 px-4 text-center text-xl font-medium text-transparent">
            0
          </span>

          <span className={'flex h-1 w-full rounded-full bg-primary-500'} />

          <div className="flex flex-col items-center gap-2 px-4">
            <p className="w-32 animate-pulse rounded-full bg-primary-500 text-center text-base font-light text-transparent">
              0
            </p>

            <p className="w-56 animate-pulse rounded-full bg-primary-500 text-center text-base font-light text-transparent">
              0
            </p>
          </div>
        </div>

        <LevelFrameSkeleton label={'Desired Level'} />
      </div>

      <div
        className={
          'relative mb-16 flex h-6 w-full flex-col items-center md:hidden'
        }
      >
        <p className="min-h-[1.5rem] -translate-y-4 px-2 text-center text-base font-medium text-white">
          0
        </p>

        <div
          className={
            'absolute flex h-6 w-[calc(100%-6rem)] flex-col rounded-b-lg border-4 border-t-0 border-primary-500 transition-colors'
          }
        />

        <div className="flex translate-y-4 flex-col items-center gap-2 text-sm">
          <p className="text-center font-light text-white">
            <b className="font-bold">0</b> to level up
          </p>

          <p className="text-center font-light text-white">
            You are earning <b className="font-bold">0</b> XP every{' '}
            <b className="font-bold">5 minutes</b>
          </p>
        </div>
      </div>
    </section>
  )
}
