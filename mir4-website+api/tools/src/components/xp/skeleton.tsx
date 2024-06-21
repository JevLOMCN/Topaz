import Input from '@/components/Input'
import LevelCalculationsSkeleton from '@/components/xp/LevelCalculations/skeleton'
import TimerSkeleton from '@/components/xp/Timer/skeleton'
import EXP from '@/icons/EXP'
import Image from 'next/image'

export default function XPPageSkeleton() {
  return (
    <>
      <TimerSkeleton />

      <section className="absolute right-4 top-24 flex flex-col items-end gap-4">
        <div className="relative grid h-12 w-12 place-items-center rounded-full border-2 border-primary-500 bg-primary-600 transition-colors hover:border-primary-400 hover:bg-primary-500 data-[state=open]:border-primary-400 data-[state=open]:bg-primary-500">
          <Image
            src="/items/peak_ticket.webp"
            alt="Secret Peak Ticket"
            width={32}
            height={32}
            className="absolute translate-x-1 rotate-12"
          />
          <Image
            src="/items/square_ticket.webp"
            alt="Magic Square Ticket"
            width={32}
            height={32}
            className="absolute -translate-x-1 -rotate-12"
          />
        </div>
        <div className="relative grid h-12 w-12 place-items-center rounded-full border-2 border-primary-500 bg-primary-600 transition-colors hover:border-primary-400 hover:bg-primary-500 data-[state=open]:border-primary-400 data-[state=open]:bg-primary-500">
          <EXP className="h-6 w-6 fill-white" />
        </div>
      </section>

      <div className="mb-4 flex !w-max flex-col gap-3">
        <div className={'mt-8 flex flex-col items-end sm:flex-row'}>
          <Input
            placeholder="Start"
            label={'Before Timer'}
            isLoading
            disabled
            suffix="%"
            className="max-w-[10rem] [&>div]:rounded-b-none [&>div]:border-b-2 [&>div]:border-primary-500 [&>div]:sm:rounded-r-none [&>div]:sm:rounded-bl-md [&>div]:sm:border-b-0 [&>div]:sm:border-r-2"
          />
          <Input
            suffix="%"
            placeholder="End"
            label={'After Timer'}
            isLoading
            disabled
            className="max-w-[10rem] flex-col-reverse sm:flex-col [&>div]:rounded-t-none [&>div]:sm:rounded-l-none [&>div]:sm:rounded-tr-md"
          />
        </div>

        <div className="flex items-center gap-3 px-8">
          <span className="h-[2px] w-full rounded-full bg-primary-100" />
          <p className="text-2xl font-bold text-primary-100">OR</p>
          <span className="h-[2px] w-full rounded-full bg-primary-100" />
        </div>

        <div className={'mb-2 flex flex-col sm:flex-row'}>
          <Input
            suffix="XP"
            label={'XP Per Minute'}
            value={0}
            isLoading
            disabled
            className="max-w-[10rem] [&>div]:rounded-b-none [&>div]:border-b-2 [&>div]:border-primary-500 [&>div]:sm:rounded-r-none [&>div]:sm:rounded-bl-md [&>div]:sm:border-b-0 [&>div]:sm:border-r-2"
          />
          <Input
            suffix="%"
            placeholder="0.0000"
            label={'Current Percentage'}
            isLoading
            disabled
            className="max-w-[10rem] flex-col-reverse sm:flex-col [&>div]:rounded-t-none [&>div]:sm:rounded-l-none [&>div]:sm:rounded-tr-md"
          />
        </div>
      </div>

      <LevelCalculationsSkeleton />
    </>
  )
}
