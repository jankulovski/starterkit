import * as React from "react"
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table"
import { Checkbox } from "@/components/ui/checkbox"
import { Button } from "@/components/ui/button"
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select"
import { ChevronLeft, ChevronsLeft, ChevronRight, ChevronsRight, MoreHorizontal } from "lucide-react"
import { cn } from "@/lib/utils"

export interface Column<T> {
    id: string
    header: string | React.ReactNode
    accessorKey?: keyof T
    cell?: (row: T) => React.ReactNode
    enableSorting?: boolean
}

export interface DataTableProps<T> {
    data: T[]
    columns: Column<T>[]
    getRowId: (row: T) => string | number
    onRowAction?: (action: string, row: T) => void
    actionMenuItems?: (row: T) => Array<{
        label: string
        action: string
        variant?: "default" | "destructive"
    }>
    pagination?: {
        currentPage: number
        lastPage: number
        perPage: number
        total: number
        onPageChange: (url: string | null) => void
        onPerPageChange?: (perPage: number) => void
        links?: Array<{
            url: string | null
            label: string
            active: boolean
        }>
    }
    enableSelection?: boolean
    selectedRows?: Set<string | number>
    onSelectionChange?: (selected: Set<string | number>) => void
    emptyMessage?: string
    className?: string
}

export function DataTable<T extends Record<string, any>>({
    data,
    columns,
    getRowId,
    onRowAction,
    actionMenuItems,
    pagination,
    enableSelection = false,
    selectedRows = new Set(),
    onSelectionChange,
    emptyMessage = "No data available",
    className,
}: DataTableProps<T>) {
    const allSelected = data.length > 0 && data.every((row) => selectedRows.has(getRowId(row)))
    const someSelected = !allSelected && data.some((row) => selectedRows.has(getRowId(row)))

    const handleSelectAll = (checked: boolean) => {
        if (!onSelectionChange) return
        if (checked) {
            const newSelected = new Set(selectedRows)
            data.forEach((row) => newSelected.add(getRowId(row)))
            onSelectionChange(newSelected)
        } else {
            const newSelected = new Set(selectedRows)
            data.forEach((row) => newSelected.delete(getRowId(row)))
            onSelectionChange(newSelected)
        }
    }

    const handleSelectRow = (row: T, checked: boolean) => {
        if (!onSelectionChange) return
        const newSelected = new Set(selectedRows)
        const rowId = getRowId(row)
        if (checked) {
            newSelected.add(rowId)
        } else {
            newSelected.delete(rowId)
        }
        onSelectionChange(newSelected)
    }

    const handleAction = (action: string, row: T) => {
        onRowAction?.(action, row)
    }

    const handlePerPageChange = (value: string) => {
        pagination?.onPerPageChange?.(parseInt(value))
    }

    return (
        <div className={cn("space-y-4", className)}>
            <div className="rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            {enableSelection && (
                                <TableHead className="w-12">
                                    <Checkbox
                                        checked={allSelected}
                                        onCheckedChange={handleSelectAll}
                                        aria-label="Select all"
                                    />
                                </TableHead>
                            )}
                            {columns.map((column) => (
                                <TableHead key={column.id}>
                                    {column.header}
                                </TableHead>
                            ))}
                            {actionMenuItems && (
                                <TableHead className="w-12"></TableHead>
                            )}
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {data.length === 0 ? (
                            <TableRow>
                                <TableCell
                                    colSpan={
                                        columns.length +
                                        (enableSelection ? 1 : 0) +
                                        (actionMenuItems ? 1 : 0)
                                    }
                                    className="h-24 text-center"
                                >
                                    {emptyMessage}
                                </TableCell>
                            </TableRow>
                        ) : (
                            data.map((row) => {
                                const rowId = getRowId(row)
                                const isSelected = selectedRows.has(rowId)
                                const menuItems = actionMenuItems?.(row) || []

                                return (
                                    <TableRow key={rowId} data-state={isSelected && "selected"}>
                                        {enableSelection && (
                                            <TableCell>
                                                <Checkbox
                                                    checked={isSelected}
                                                    onCheckedChange={(checked) =>
                                                        handleSelectRow(row, checked as boolean)
                                                    }
                                                    aria-label={`Select row ${rowId}`}
                                                />
                                            </TableCell>
                                        )}
                                        {columns.map((column) => (
                                            <TableCell key={column.id}>
                                                {column.cell
                                                    ? column.cell(row)
                                                    : column.accessorKey
                                                      ? String(row[column.accessorKey] ?? "")
                                                      : null}
                                            </TableCell>
                                        ))}
                                        {actionMenuItems && (
                                            <TableCell>
                                                <DropdownMenu>
                                                    <DropdownMenuTrigger asChild>
                                                        <Button
                                                            variant="ghost"
                                                            className="h-8 w-8 p-0"
                                                        >
                                                            <span className="sr-only">Open menu</span>
                                                            <MoreHorizontal className="h-4 w-4" />
                                                        </Button>
                                                    </DropdownMenuTrigger>
                                                    <DropdownMenuContent align="end">
                                                        {menuItems.map((item, index) => (
                                                            <DropdownMenuItem
                                                                key={index}
                                                                variant={item.variant}
                                                                onClick={() =>
                                                                    handleAction(item.action, row)
                                                                }
                                                            >
                                                                {item.label}
                                                            </DropdownMenuItem>
                                                        ))}
                                                    </DropdownMenuContent>
                                                </DropdownMenu>
                                            </TableCell>
                                        )}
                                    </TableRow>
                                )
                            })
                        )}
                    </TableBody>
                </Table>
            </div>

            {pagination && (
                <div className="flex items-center justify-between border-t px-2 py-4">
                    <div className="text-sm text-muted-foreground">
                        {enableSelection && selectedRows.size > 0
                            ? `${selectedRows.size} of ${pagination.total} row(s) selected`
                            : `${pagination.total} row(s) total`}
                    </div>
                    <div className="flex items-center gap-4">
                        {pagination.onPerPageChange && (
                            <div className="flex items-center gap-2">
                                <span className="text-sm text-muted-foreground">Rows per page</span>
                                <Select
                                    value={String(pagination.perPage)}
                                    onValueChange={handlePerPageChange}
                                >
                                    <SelectTrigger className="h-8 w-[70px]">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="10">10</SelectItem>
                                        <SelectItem value="25">25</SelectItem>
                                        <SelectItem value="50">50</SelectItem>
                                        <SelectItem value="100">100</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        )}
                        <div className="flex items-center gap-2">
                            <span className="text-sm text-muted-foreground">
                                Page {pagination.currentPage} of {pagination.lastPage}
                            </span>
                            <div className="flex gap-1">
                                {(() => {
                                    const firstLink = pagination.links?.[0]
                                    const lastLink = pagination.links?.[pagination.links.length - 1]
                                    // Laravel pagination: first link is usually "Previous", last is usually "Next"
                                    // But we need to find them by checking the label patterns
                                    const prevLink = pagination.links?.find(
                                        (link) => 
                                            link.label.includes("Previous") || 
                                            link.label.includes("&laquo;") ||
                                            (link.label === "«" && firstLink && firstLink !== link)
                                    ) || (firstLink && firstLink.label !== "1" ? firstLink : null)
                                    const nextLink = pagination.links?.find(
                                        (link) => 
                                            link.label.includes("Next") || 
                                            link.label.includes("&raquo;") ||
                                            (link.label === "»" && lastLink && lastLink !== link)
                                    ) || (lastLink && lastLink.label !== String(pagination.lastPage) ? lastLink : null)

                                    return (
                                        <>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                className="h-8 w-8 p-0"
                                                onClick={() => {
                                                    if (pagination.links && pagination.links.length > 0) {
                                                        // Find the "1" page link
                                                        const firstPageLink = pagination.links.find(
                                                            (link) => link.label === "1"
                                                        )
                                                        if (firstPageLink) {
                                                            pagination.onPageChange(firstPageLink.url)
                                                        } else if (firstLink) {
                                                            pagination.onPageChange(firstLink.url)
                                                        }
                                                    }
                                                }}
                                                disabled={
                                                    !pagination.links ||
                                                    pagination.links.length === 0 ||
                                                    pagination.currentPage === 1
                                                }
                                            >
                                                <span className="sr-only">Go to first page</span>
                                                <ChevronsLeft className="h-4 w-4" />
                                            </Button>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                className="h-8 w-8 p-0"
                                                onClick={() => {
                                                    if (prevLink) {
                                                        pagination.onPageChange(prevLink.url)
                                                    }
                                                }}
                                                disabled={
                                                    !prevLink ||
                                                    !prevLink.url ||
                                                    pagination.currentPage === 1
                                                }
                                            >
                                                <span className="sr-only">Go to previous page</span>
                                                <ChevronLeft className="h-4 w-4" />
                                            </Button>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                className="h-8 w-8 p-0"
                                                onClick={() => {
                                                    if (nextLink) {
                                                        pagination.onPageChange(nextLink.url)
                                                    }
                                                }}
                                                disabled={
                                                    !nextLink ||
                                                    !nextLink.url ||
                                                    pagination.currentPage === pagination.lastPage
                                                }
                                            >
                                                <span className="sr-only">Go to next page</span>
                                                <ChevronRight className="h-4 w-4" />
                                            </Button>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                className="h-8 w-8 p-0"
                                                onClick={() => {
                                                    if (pagination.links && pagination.links.length > 0) {
                                                        // Find the last page number link
                                                        const lastPageNumber = String(pagination.lastPage)
                                                        const lastPageLink = pagination.links.find(
                                                            (link) => link.label === lastPageNumber
                                                        )
                                                        if (lastPageLink) {
                                                            pagination.onPageChange(lastPageLink.url)
                                                        } else if (lastLink) {
                                                            pagination.onPageChange(lastLink.url)
                                                        }
                                                    }
                                                }}
                                                disabled={
                                                    !pagination.links ||
                                                    pagination.links.length === 0 ||
                                                    pagination.currentPage === pagination.lastPage
                                                }
                                            >
                                                <span className="sr-only">Go to last page</span>
                                                <ChevronsRight className="h-4 w-4" />
                                            </Button>
                                        </>
                                    )
                                })()}
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </div>
    )
}

