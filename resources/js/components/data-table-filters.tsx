import * as React from "react"
import { Input } from "@/components/ui/input"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from "@/components/ui/popover"
import { Search, Plus, X } from "lucide-react"
import { cn } from "@/lib/utils"

export interface FilterOption {
    value: string
    label: string
    count?: number
}

export interface FilterConfig {
    id: string
    label: string
    options: FilterOption[]
}

export interface DataTableFiltersProps {
    searchValue: string
    onSearchChange: (value: string) => void
    onSearchSubmit?: () => void
    filters: FilterConfig[]
    selectedFilters: Record<string, string[]>
    onFilterChange: (filterId: string, values: string[]) => void
    onReset: () => void
    searchPlaceholder?: string
}

export function DataTableFilters({
    searchValue,
    onSearchChange,
    onSearchSubmit,
    filters,
    selectedFilters,
    onFilterChange,
    onReset,
    searchPlaceholder = "Filter tasks...",
}: DataTableFiltersProps) {
    const handleSearchKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
        if (e.key === "Enter" && onSearchSubmit) {
            e.preventDefault()
            onSearchSubmit()
        }
    }

    const getFilterDisplay = (filter: FilterConfig) => {
        const selected = selectedFilters[filter.id] || []
        if (selected.length === 0) {
            return null
        }
        if (selected.length <= 2) {
            return selected.map((value) => {
                const option = filter.options.find((opt) => opt.value === value)
                return option?.label || value
            }).join(", ")
        }
        return `${selected.length} selected`
    }

    const hasActiveFilters = Object.values(selectedFilters).some(
        (values) => values.length > 0
    ) || searchValue.length > 0

    return (
        <div className="flex items-center gap-2 flex-wrap">
            <div className="relative w-[250px]">
                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                    type="text"
                    placeholder={searchPlaceholder}
                    value={searchValue}
                    onChange={(e) => onSearchChange(e.target.value)}
                    onKeyDown={handleSearchKeyDown}
                    className="pl-9"
                />
            </div>

            {filters.map((filter) => {
                const selected = selectedFilters[filter.id] || []
                const display = getFilterDisplay(filter)
                const isActive = selected.length > 0

                return (
                    <Popover key={filter.id}>
                        <PopoverTrigger asChild>
                            <Button
                                variant="outline"
                                className={cn(
                                    "h-9 border-dashed",
                                    isActive && "border-solid"
                                )}
                            >
                                <Plus className="mr-2 h-4 w-4" />
                                {filter.label}
                                {display && (
                                    <>
                                        {selected.length <= 2 && (
                                            <div className="ml-2 flex gap-1">
                                                {selected.map((value) => {
                                                    const option = filter.options.find(
                                                        (opt) => opt.value === value
                                                    )
                                                    return (
                                                        <Badge
                                                            key={value}
                                                            variant="secondary"
                                                            className="rounded-sm px-1 font-normal"
                                                        >
                                                            {option?.label || value}
                                                        </Badge>
                                                    )
                                                })}
                                            </div>
                                        )}
                                        {selected.length > 2 && (
                                            <Badge
                                                variant="secondary"
                                                className="ml-2 rounded-sm px-1 font-normal"
                                            >
                                                {display}
                                            </Badge>
                                        )}
                                    </>
                                )}
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent className="w-[200px] p-0" align="start">
                            <div className="p-2">
                                <Input
                                    placeholder={filter.label}
                                    className="h-8"
                                />
                            </div>
                            <div className="max-h-[300px] overflow-y-auto">
                                {filter.options.map((option) => {
                                    const isSelected = selected.includes(option.value)
                                    return (
                                        <div
                                            key={option.value}
                                            className="flex items-center justify-between px-2 py-1.5 hover:bg-accent cursor-pointer"
                                            onClick={() => {
                                                const newSelected = isSelected
                                                    ? selected.filter((v) => v !== option.value)
                                                    : [...selected, option.value]
                                                onFilterChange(filter.id, newSelected)
                                            }}
                                        >
                                            <div className="flex items-center gap-2">
                                                <div
                                                    className={cn(
                                                        "h-4 w-4 rounded border flex items-center justify-center",
                                                        isSelected
                                                            ? "bg-primary border-primary"
                                                            : "border-input"
                                                    )}
                                                >
                                                    {isSelected && (
                                                        <svg
                                                            className="h-3 w-3 text-primary-foreground"
                                                            fill="none"
                                                            viewBox="0 0 24 24"
                                                            stroke="currentColor"
                                                        >
                                                            <path
                                                                strokeLinecap="round"
                                                                strokeLinejoin="round"
                                                                strokeWidth={2}
                                                                d="M5 13l4 4L19 7"
                                                            />
                                                        </svg>
                                                    )}
                                                </div>
                                                <span className="text-sm">{option.label}</span>
                                            </div>
                                            {option.count !== undefined && (
                                                <span className="text-xs text-muted-foreground">
                                                    {option.count}
                                                </span>
                                            )}
                                        </div>
                                    )
                                })}
                            </div>
                            {selected.length > 0 && (
                                <>
                                    <div className="border-t" />
                                    <div className="p-2">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            className="w-full justify-start"
                                            onClick={() => onFilterChange(filter.id, [])}
                                        >
                                            Clear filters
                                        </Button>
                                    </div>
                                </>
                            )}
                        </PopoverContent>
                    </Popover>
                )
            })}

            {hasActiveFilters && (
                <Button
                    variant="ghost"
                    size="sm"
                    className="h-9"
                    onClick={onReset}
                >
                    Reset
                    <X className="ml-2 h-4 w-4" />
                </Button>
            )}
        </div>
    )
}

