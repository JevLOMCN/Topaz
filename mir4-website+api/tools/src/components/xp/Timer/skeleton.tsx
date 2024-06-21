import Input from '@/components/Input'
import Image from 'next/image'

export default function TimerSkeleton() {
  return (
    <section
      id="experienceTimer"
      className="relative flex w-full max-w-[20rem] flex-col items-center gap-6 overflow-hidden rounded-lg border-2 border-[#7E73AD30] py-4 backdrop-blur-2xl"
    >
      <Image
        alt=""
        src="/images/timer-background.svg"
        className="pointer-events-none absolute object-cover"
        fill
        sizes="(max-width: 768px) 155px, (max-width: 1200px) 320px"
        priority
      />

      <div
        className={
          'flex flex-row items-center gap-2 text-5xl font-bold text-white transition-transform duration-300 sm:text-6xl'
        }
      >
        <Input
          placeholder="05"
          label={'Minutes'}
          value={'5'}
          disabled
          className={
            'transition-colors duration-300 [&>div>input]:w-24 [&>div>input]:text-5xl [&>div>input]:font-bold [&>div>input]:sm:text-6xl [&>div]:transition-[transform,_background-color] [&>div]:duration-300'
          }
        />
        :
        <Input
          placeholder="05"
          label={'Seconds'}
          value={'0'}
          disabled
          className={
            'transition-colors duration-300 [&>div>input]:w-24 [&>div>input]:text-5xl [&>div>input]:font-bold [&>div>input]:sm:text-6xl [&>div]:transition-[transform,_background-color] [&>div]:duration-300'
          }
        />
      </div>

      <div className="flex flex-row gap-3">
        <div className="rounded-[4px] bg-[#368D6E] px-4 py-2 text-xs font-bold uppercase text-white disabled:bg-opacity-50">
          Start
        </div>

        <div className="rounded-[4px] bg-[#473E65] bg-opacity-50 px-4 py-2 text-xs font-bold uppercase text-white">
          Reset
        </div>
      </div>
    </section>
  )
}
