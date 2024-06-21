import Image from 'next/image'
import InnerTimer from './InnerTimer'

export default function Timer() {
  return (
    <section
      id="experienceTimer"
      className="relative flex w-full max-w-[20rem] flex-col items-center gap-6 overflow-hidden rounded-lg border-2 border-[#7E73AD30] py-4 backdrop-blur-2xl"
    >
      <InnerTimer />

      <Image
        alt=""
        src="/images/timer-background.svg"
        className="pointer-events-none absolute object-cover"
        fill
        sizes="(max-width: 768px) 155px, (max-width: 1200px) 320px"
        priority
      />
    </section>
  )
}
